# ProcessRunner 設計メモ

## 目的

この文書は、PHP から外部プロセスを実行するための薄い Framework 機能 `ProcessRunner` を追加する理由、責務、classic runtime / worker runtime での注意点、実装手順を整理する。

対象の最初の利用箇所は CMS の画像派生生成である。現在は `apps/cms/src/Infrastructure/Media/VipsImageVariantGenerator.php` が `proc_open()` を直接扱っている。この実装を Framework の安全な実行プリミティブへ移す。

この機能の主な利用者像は、Linux / bash / サーバー運用の感覚はあるが、PHP request lifecycle の中で外部プロセスを安全に扱う経験は薄い開発者である。コマンド自体の意味、PATH、権限、stderr は理解できても、PHP から呼ぶ時には shell escaping、pipe drain、timeout、resource close、worker mode での state 残存といった別の罠がある。`ProcessRunner` はその罠を Framework 側に閉じ、呼び出し側には「配列 command を渡し、結果を見る」だけを要求するための部品である。

## 背景

`proc_open()` は PHP で外部コマンドを扱う標準機能だが、正しく使うには細かい注意が多い。

- string command では shell escaping を毎回正しく扱う必要がある。
- stdout / stderr pipe を読まないと child process が詰まる可能性がある。
- pipe を開いたまま `proc_close()` すると deadlock の原因になる。
- timeout 時に `proc_terminate()` するだけでは終了待ちにならない。
- `proc_terminate()` は子プロセスの子プロセスまでは必ずしも掃除しない。
- request 中の例外、fatal error、client disconnect、PHP timeout の経路でも resource / child process の扱いを意識する必要がある。

これらは CMS 画像変換固有の業務知識ではなく、PHP runtime 上で外部プロセスを安全に呼ぶための低レベル実行基盤である。各アプリの Infrastructure が毎回 `proc_open()` を手書きするより、Framework が限定された安全な形を提供する方がよい。

## 現行 Framework との関係

現行 Framework は HTTP request / response、routing、middleware pipeline、DI container、bootstrap、classic / FrankenPHP worker runtime 選択を提供している。

Framework は FrankenPHP や Caddy の OS プロセス自体を起動・監督しているわけではない。一方で、PHP process 内で request をどう受け、どう application へ流すかは Framework の責務である。

`ProcessRunner` は HTTP そのものではないが、`proc_open()` という PHP process 内の低レベル実行 API を安全な形に制限する部品である。これは `Request` / `Response` / `Runtime` と同じく、アプリ開発者が毎回調べて書くべきではない基盤寄りの処理とみなせる。

## classic runtime での考慮

classic runtime では、通常は request ごとに PHP script が実行される。長寿命 worker よりも request 間で PHP memory state が残るリスクは小さい。

ただし、外部プロセスは OS 上の別プロセスである。PHP request が終わる、または PHP script が異常終了することと、child process が必ず消えることは同義ではない。

classic runtime でも以下は必要である。

- command は shell を経由しない配列形式に限定する。
- stdin / stdout / stderr pipe は必ず close する。
- stdout / stderr は child process が詰まらないように読み取る。
- timeout を設ける。
- timeout 時は terminate だけでなく、短い猶予後の強制終了を検討する。
- 実行結果は exit code / stdout / stderr / timeout を明示的に返す。

つまり classic runtime では「request 間の PHP memory leak」は主問題ではないが、「child process の残存」「pipe deadlock」「command injection」は依然として問題になる。

## worker runtime での考慮

FrankenPHP worker mode では PHP application が memory に保持され、複数 request を同じ PHP process が処理する。

そのため classic runtime のリスクに加え、次が重要になる。

- process resource や pipe resource を request をまたいで保持してはならない。
- `ProcessRunner` は stateless にする。
- `ProcessRunner` は process handle を呼び出し側へ返さない。
- `ProcessRunner` は async / background process を提供しない。
- `Container::workerSingleton()` で保持されても安全なように、内部 mutable state を持たない。
- 実行中 process の情報を class property に置かない。
- stdout / stderr buffer など request 固有の値を object property に残さない。

worker mode で特に避けたいのは、ある request で起動した外部プロセスの handle が PHP object に残り、次 request の処理と混ざることである。同期 `run()` に閉じ、戻り値だけを返す設計にする。

## 提供する API

配置は `src/Framework/Process` とする。

最小構成:

```text
src/Framework/Process/
  ProcessRunner.php
  ProcessResult.php
```

想定 API:

```php
use App\Framework\Process\ProcessRunner;

$result = $runner->run(
    command: ['vipsthumbnail', $source, '--size', 'down', '-s', '1600x1600', '-o', $destination],
    timeoutSeconds: 20,
);

if (!$result->successful()) {
    // アプリ側でログ、fallback、業務判断を行う
}
```

`ProcessRunner::run()` の引数:

- `command: list<string>`
- `timeoutSeconds: int`
- `cwd: ?string = null`
- `env: ?array<string, string> = null`
- `maxStdoutBytes: int = 1048576`
- `maxStderrBytes: int = 1048576`
- `terminateGraceSeconds: int = 2`

`ProcessResult` の値:

- `exitCode: int|null`
- `stdout: string`
- `stderr: string`
- `timedOut: bool`
- `startupError: ?string`
- `successful(): bool`
- `started(): bool`
- `failedToStart(): bool`

`successful()` は `startupError === null && timedOut === false && exitCode === 0` とする。

`started()` は `startupError === null`、`failedToStart()` は `startupError !== null` とする。

## 持たせない責務

Framework `ProcessRunner` は外部プロセス実行の形だけを安全にする。以下は持たない。

- `vipsthumbnail` や `vips` など特定コマンド名。
- 画像変換仕様。
- 許可コマンド一覧。
- retry 方針。
- 業務ログ方針。
- ユーザー向けエラーメッセージ。
- 保存先命名。
- queue / background job。
- async process 管理。
- daemon / supervisor。

これらは `apps/cms` の Infrastructure、または将来の worker / queue 基盤で扱う。

Framework は command allowlist を持たない。ただし呼び出し側は executable を固定するか、明示 allowlist から選ぶ。ユーザー入力で executable を直接選ばせてはならない。Framework が守るのは shell 経由の injection や resource leak を避ける実行形式であり、どの外部コマンドを実行してよいかという業務/運用判断ではない。

## command の扱い

`command` は必ず `list<string>` とし、string command は受け取らない。

理由:

- PHP 7.4 以降、`proc_open()` は array command を受け取れる。
- array command は shell を経由せず、PHP が必要な argument escaping を扱う。
- string command を許すと、呼び出し側が shell escaping を正しく行う必要が出る。

空配列、空文字の command element は `InvalidArgumentException` とする。

`proc_open()` 起動時に PHP warning が出る場合は `@` で抑制しない。`ProcessRunner` が局所 `set_error_handler()` で warning message を捕捉し、起動できなかった場合は `ProcessResult::startupError` に格納する。error handler は `proc_open()` 呼び出し範囲だけに限定し、必ず `restore_error_handler()` する。

## timeout と終了処理

timeout 時の手順:

1. `proc_terminate($process, SIGTERM)` を呼ぶ。`SIGTERM` 定数が使えない環境では numeric signal `15` を使う。
2. `terminateGraceSeconds` の間、短い sleep で `proc_get_status()` を polling する。
3. まだ running なら POSIX 環境では `proc_terminate($process, SIGKILL)` を呼ぶ。`SIGKILL` 定数が使えない環境では numeric signal `9` を使う。
4. 短く再 polling し、終了確認を試みる。
5. pipe を close する。
6. `proc_close()` を呼ぶ。
7. `ProcessResult(timedOut: true, exitCode: null or close result, stdout, stderr)` を返す。

注意:

- `proc_terminate()` は終了待ちではない。
- `proc_terminate()` は child process のさらに子プロセスまでは必ずしも消さない。
- `proc_close()` は process 終了を待つ。timeout 後の close は best effort cleanup として行うが、外部コマンドが signal を無視する場合は request を詰まらせるリスクが残る。
- 今回の用途では shell を経由しない array command を使うため、余計な `/bin/sh -c` process は作らない。
- process group 全体の kill は OS 依存が強く、初期実装では扱わない。

将来、外部コマンドが子プロセスをさらに起動する用途が出た場合は、POSIX process group を作る専用 runner、または queue/worker 側の supervisor 設計を検討する。

## stdout / stderr の扱い

stdout / stderr は pipe にして、実行中に読み取る。

目的:

- child process が pipe buffer 詰まりで止まることを避ける。
- 失敗時に調査できる最小限の stderr を `ProcessResult` へ残す。

ただし無制限に保持しない。stdout は `maxStdoutBytes`、stderr は `maxStderrBytes` を超えた分を捨てる。初期値はそれぞれ 1MB とする。

stdin は初期実装では使わないため、open 後すぐ close する。

## 例外方針

`ProcessRunner::run()` は、command validation の失敗以外では原則として例外を投げず、`ProcessResult` を返す。

例外にするもの:

- 空 command。
- 空文字 element。
- timeoutSeconds が 1 未満。
- maxStdoutBytes / maxStderrBytes が 0 未満。
- cwd が指定されていて directory ではない。

結果で返すもの:

- `proc_open()` 失敗。
- exit code 非 0。
- timeout。

理由:

- 外部コマンド失敗は業務側が fallback できる通常の失敗として扱いたい。
- CMS 画像派生生成では、変換失敗時も元画像アップロードは成立させる。

## CMS 画像派生生成への適用

現在:

```text
VipsImageVariantGenerator
  - proc_open()
  - proc_get_status()
  - proc_terminate()
  - proc_close()
```

変更後:

```text
VipsImageVariantGenerator
  - ProcessRunner を注入
  - vipsthumbnail の command 配列を組み立てる
  - ProcessResult を見て成功/失敗を判断する
```

`VipsImageVariantGenerator` は引き続き `apps/cms/src/Infrastructure/Media` に置く。Framework は画像変換を知らない。

## 実装手順

1. `src/Framework/Process/ProcessResult.php` を追加する。
2. `src/Framework/Process/ProcessRunner.php` を追加する。
3. `ProcessRunnerTest` を追加する。
   - 正常終了で stdout / stderr / exitCode を取得できる。
   - 非 0 exit code を `successful() === false` として返す。
   - 存在しない executable で `startupError` が入る。
   - timeout で `timedOut === true` になる。
   - command validation が効く。
   - maxStdoutBytes / maxStderrBytes で stdout / stderr がそれぞれ制限される。
4. `VipsImageVariantGenerator` から private `run()` / `closePipes()` を削除し、`ProcessRunner` を注入する。
5. `VipsImageVariantGeneratorTest` を新 API に合わせる。
6. `docs/decisions.md` の ProcessRunner 採用判断を追記する。
7. `docs/current_status.md` を更新する。
8. `docker compose run --rm app composer test` を実行する。
9. `docker compose run --rm app composer analyse` を実行する。
10. app コンテナ内で libvips smoke を再実行する。
11. coherent work unit として commit する。

## テスト方針

ProcessRunner のテストでは、外部依存の大きいコマンドに依存しすぎない。

Linux コンテナ上で動く前提のため、以下は許容する。

- `/bin/sh`
- `php` CLI
- `sleep`

ただし string shell command は runner の public API としては許さない。テストで `/bin/sh` を command の executable として使う場合も、`['/bin/sh', '-c', '...']` の配列 command として渡す。

timeout テストは長すぎない値にする。`sleep 2` に対して `timeoutSeconds: 1` 程度を使う。

起動失敗テストは、存在しない executable を `command` の先頭に渡して `startupError !== null` を確認する。PHP warning がテスト出力に漏れないことも確認する。

## PHPDoc / コードコメントに残す契約

この機能は低レベル API を安全側へ倒すための部品なので、実装コード側にも契約を明記する。

`ProcessRunner::run()` の PHPDoc に書くこと:

- `command` は shell command string ではなく `list<string>` である。
- command は shell を経由せず `proc_open()` の array command として実行する。
- executable は呼び出し側で固定または allowlist 化する。
- timeout 時は terminate / kill / close を best effort で行う。
- stdout / stderr は指定 byte 数まで保持し、超過分は捨てる。
- 起動 warning は `@` で抑制せず、`ProcessResult::startupError` として返す。
- 戻り値は外部コマンド失敗を表す通常結果であり、validation failure 以外は原則例外にしない。

`ProcessResult` の PHPDoc に書くこと:

- `exitCode === null` は、起動失敗または timeout などで通常の終了コードが確定しない状態を表す。
- `startupError !== null` は process が起動しなかったことを表す。
- `timedOut === true` は runner の timeout に達したことを表し、外部 process group 全体の完全終了保証ではない。
- `successful()` は起動成功、timeout なし、exit code 0 の場合だけ true を返す。

`VipsImageVariantGenerator` 側に書くこと:

- 画像変換失敗はアップロード失敗ではない。
- `ProcessRunner` の失敗結果は派生画像未生成として扱う。
- `vipsthumbnail` の command 配列構築と画像変換仕様は CMS Infrastructure の責務である。

## レビュー観点

- Framework に置く責務として大きすぎないか。
- `ProcessRunner` が画像変換や CMS 業務に依存していないか。
- worker mode で request をまたぐ state が残らないか。
- timeout 時に process resource / pipe resource を閉じ切れるか。
- `proc_terminate()` の限界を過信していないか。
- `proc_open()` warning を `@` で握りつぶさず、`startupError` として返しているか。
- stdout / stderr を無制限に memory へ積まないか。
- shell string を受け取れる逃げ道を作っていないか。
- executable の固定/allowlist は呼び出し側責務として明示されているか。
- 呼び出し側が外部コマンド失敗を業務判断として扱える戻り値になっているか。

## 未対応として明示すること

初期実装では以下を扱わない。

- background process。
- async process。
- process pool。
- process group kill。
- Windows 完全対応。
- stdin へのデータ投入。
- streaming output callback。
- retry。
- command allowlist。

これらは必要になった時点で、用途とリスクを分けて設計する。
