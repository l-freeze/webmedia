# リファクタリング作業チェックリスト

## PHP 8.5 一貫性修正

コードベース全体で `readonly` クラス・コンストラクタプロモーションが統一されているが、
以下の2ファイルのみスタイルが揃っていなかったため修正した。

- [x] `apps/shared/src/Markdown/Infrastructure/Markdown.php`
  - `final class` → `final readonly class` に変更
  - プロパティはコンストラクタ引数ではないためプロモーション不可だが、`readonly` クラス化により一貫性を確保
- [x] `apps/shared/src/View/Infrastructure/LatteTemplateRenderer.php`
  - `$engine`・`$templateDirectory` プロパティに `readonly` を付与
  - `$globalProviders` は `addGlobalProvider()` で変更されるため `readonly` 不可
  - クラス全体を `readonly` にできないため、個別プロパティへの付与で対応
- [x] `tests/Shared/View/LatteTemplateRendererTest.php` を新規作成
  - テンプレートレンダリングと `addGlobalProvider` の動作を検証

このファイルは、PHPStan 対応と設計整理を途中から再開できるようにするための作業メモである。

## PHPStan 対応

- [ ] PHPStan の現状エラーをカテゴリ別に整理する
- [x] PDO 周りの型安全化を行う
  - [x] `PDO::prepare()` / `PDO::query()` の `false` を Framework 側で閉じ込める
  - [x] `fetch()` の戻り値を `array<string, mixed>|null` に正規化する
  - [x] `fetchAll()` の戻り値を `list<array<string, mixed>>` に正規化する
  - [x] `fetchColumn()` の戻り値を用途別に正規化する
  - [x] Infrastructure/Persistence から直接 `PDOStatement|false` を扱う箇所を減らす
- [ ] callback 系配列関数を `foreach` へ寄せて解消する
  - [ ] `array_map()` を `foreach` へ置き換える
  - [ ] `array_filter()` を `foreach` へ置き換える
  - [ ] `array_reduce()` を `foreach` へ置き換える
  - [ ] `array_walk()` を使わないことを確認する
- [ ] `date()` / `strtotime()` の `false` 問題を日付 helper へ閉じ込める
- [ ] `Article::fromInput()` / `Page::fromInput()` の `mixed` cast を入力正規化 helper へ寄せる
- [ ] Controller / Application / Domain / Infrastructure の境界違反を PHPStan で検出できるようにする
- [ ] `composer analyse` を成功させる

## Worker Mode 対応

**禁止: コンテナのリクエスト間再利用**

`loadContainer()` は `require` で毎リクエストコンテナを再生成する。これは意図的な設計であり、パフォーマンス最適化を目的にコンテナをリクエスト間で再利用してはならない。`Csrf::class` の singleton が別ユーザーの `Session` を保持し続けるセキュリティリスクがあり、利益よりリスクが大きい。

Worker mode の恩恵はプロセス起動コストの排除で十分に得られる。`Application` インスタンスの長寿命化によりルート解析とミドルウェア設定の読み込みは起動時の1回で済む。

- [ ] FrankenPHP Worker mode の起動エントリを追加する
- [ ] Worker mode で連続リクエストして session / csrf / flash が混線しないことを確認する

## Middleware / Guard

- [ ] `cms.auth` middleware を実装する
- [ ] Controller 内の `Session` によるログイン判定を middleware へ移す
- [ ] `CurrentUser` DTO を導入する
- [ ] 認可が必要な UseCase に `CurrentUser` を渡す
- [ ] 重要操作には UseCase-level authorization を追加する

## Logging / Error Handling

- [ ] `LoggerInterface` の実装を追加する
- [ ] `AuditLoggerInterface` の実装を追加する
- [ ] 例外を HTTP response に変換する error handler を追加する
- [ ] request id を発行し、log / audit log に載せる
- [ ] 500 系のユーザー表示と内部ログを分離する

## Architecture Test

- [ ] `apps/front` から `App\Cms` へ依存していないことを検査する
- [ ] `apps/cms` から `App\Front` へ依存していないことを検査する
- [ ] `Application` が `Infrastructure` に依存していないことを検査する
- [ ] `Domain` が `Application` / `Infrastructure` に依存していないことを検査する
- [ ] Controller の不要な `as` alias を検査する
- [ ] UseCase `Input` の named arguments 利用を検査する

## リファクタリング評価結果 2026-06-07

### 総合評価

リファクタリング方針は大筋で適切。

このプロジェクトの方針である、CMS / Front の業務ロジック分離、Framework と Shared の責務分離、PHPStan level max による型安全性向上、FrankenPHP Worker mode を見据えた request 固有状態の分離と整合している。

ただし、現在のチェックリストには実装済みの項目と未実装の項目が混在しているため、そのまま上から順に実施すると重複作業や手戻りが発生する。実施前に「完了済み」「継続」「方針修正」を分けて扱うのがよい。

### 適切な項目

- PHP 8.5 一貫性修正は適切。
  - 既存コードは `readonly` class / readonly property / constructor promotion を積極的に使う方針で揃っているため、局所的なスタイル差分を解消する意味がある。
  - `LatteTemplateRenderer` は `addGlobalProvider()` によって `$globalProviders` が変更されるため、クラス全体を `readonly` にしない判断も妥当。
- PDO 周りの型安全化は適切。
  - `PdoHelper` / `PdoRow` に `PDO::prepare()`、`fetch()`、`fetchAll()`、`fetchColumn()` の曖昧さを閉じ込める方針は、Infrastructure 境界で DB 由来の `mixed` を正規化できる。
  - Query / Repository 側の DTO 生成を読みやすくし、PHPStan の指摘も局所化できる。
- callback 系配列関数を `foreach` へ寄せる方針は適切。
  - `docs/webmedia_architecture_rules.md` の配列処理ルールと一致している。
  - 現状でも Controller、Persistence、container config に `array_map()` / `array_filter()` が残っているため、継続対象として妥当。
  - ただし、これは動作改善というより規約・可読性・PHPStan 安定化のためのリファクタリングなので、機能変更と混ぜずに小さく進めるべき。
- `date()` / `strtotime()` の扱いを helper に閉じ込める方針は適切。
  - 現状 `PdoRow::date()` 内に `strtotime()` / `date()` が残っており、Persistence でも `date('Y-m-d H:i:s')` が複数箇所にある。
  - 日付生成と DB 表現を一箇所に寄せると、timezone、false、format の扱いを統一できる。
- `Article::fromInput()` / `Page::fromInput()` の入力正規化 helper 化は適切。
  - Domain Entity が `array<string, mixed>` の入力配列を直接解釈している状態は、Application と Domain の境界として弱い。
  - Controller / Input / Form normalization 側で正規化し、Domain は Value Object を受け取る形へ寄せる方がよい。
- Middleware / Guard の方向性は適切。
  - `RequireCmsSessionMiddleware` はすでに存在し、Controller からログイン判定を外す方向は正しい。
  - 次の段階で `CurrentUser` DTO と UseCase-level authorization を導入する方針も、`docs/authorization_workflow_extension.md` や `docs/knowledge.md` の考え方と一致している。
- Architecture Test は適切。
  - 現在のディレクトリ構成は境界が多いため、依存方向の破れを PHPStan や独自テストで検出できるようにする価値が高い。
  - 特に `Application -> Infrastructure`、`Domain -> Application / Infrastructure` の禁止は優先度が高い。

### すでに実施済みまたは要更新の項目

- `LoggerInterface` の実装追加は実施済み。
  - `apps/shared/src/Logging/Infrastructure/JsonFileLogger.php` が追加され、`apps/shared/config/container.php` で `Psr\Log\LoggerInterface` に登録済み。
  - 今後は「実装追加」ではなく「request id 付与」「例外ログ連携」「用途別 channel 相当の整理」を対象にするのがよい。
- `AuditLoggerInterface` の実装追加は一部実施済み。
  - `apps/shared/src/Audit/Application/AuditLoggerInterface.php` と `PlaceholderAuditLogger` は存在する。
  - ただし placeholder は呼び出されると例外を投げるため、実運用上は未完成。チェックリスト上は「本実装追加」または「CMS 重要操作への接続」に書き換えるのが適切。
- 例外を HTTP response に変換する error handler の差し込み口は実施済み。
  - `ExceptionHandlerInterface`、`RethrowingExceptionHandler`、`PlainTextExceptionHandler` が存在し、`Application` から利用されている。
  - 残作業はアプリケーション固有の 500 表示、ログ連携、開発/本番の出し分け。
- `cms.auth` middleware は実施済みとみなせる。
  - 名前は `cms.auth` ではないが、`RequireCmsSessionMiddleware` と `apps/cms/config/middleware.php` により CMS 認証 middleware は存在する。
  - 残作業は `CurrentUser` の生成、認可、監査ログとの連携。
- `composer analyse` は現状成功済みとして扱える。
  - `docs/current_status.md` では PHPStan level max + baseline で No errors が記録されている。
  - 今後は「成功させる」ではなく「baseline 削減」「新規違反防止」「architecture rule の追加」に分けるのがよい。

### 注意が必要な項目

- Worker Mode 対応は適切だが、container 再利用の最適化は行わない。
  - container をリクエスト間で再利用すると `Csrf::class` の singleton が別ユーザーのセッションを保持するセキュリティリスクがあり、パフォーマンス上の利益を大きく上回る。
  - Worker mode の恩恵はプロセス起動コストの排除で十分であり、`loadContainer()` が毎リクエスト container を再生成する現状の設計はそのまま維持する。
  - Worker mode 対応の作業はエントリポイントの追加と動作確認に限定される。
- callback 系配列関数の全面置換は妥当だが、container config の `$csv` のような小さな正規化まで一括で置換すると差分が広がる。
  - まず Controller の `toArray()` 変換、Persistence の row mapping、taxonomy の分割など、型境界に近い箇所を優先するのがよい。
- `date()` / `strtotime()` helper は Framework に置くか Shared に置くかを慎重に決める。
  - DB row の文字列変換や永続化時刻の生成は Shared / Infrastructure 寄り。
  - Request / Runtime から現在時刻を注入したい場合は Framework 側の clock interface も候補になる。
  - 最初は Shared の小さな date/time helper から始め、必要になったら Clock 抽象へ広げるのが安全。

### 優先順位の提案

1. 実装済み項目をチェックリスト上で整理する。
2. callback 系配列関数を `foreach` へ置換し、差分ごとに `composer analyse` / `composer test` を通す。
3. `date()` / `strtotime()` と永続化時刻生成を helper に閉じ込める。
4. `Article::fromInput()` / `Page::fromInput()` の `mixed` 入力を正規化し、Domain Entity から配列解釈を外す。
5. Architecture Test を追加し、依存方向の破れを検出する。
6. `CurrentUser` と UseCase-level authorization を追加する。
7. AuditLogger の本実装と CMS 重要操作への接続を行う。
8. request scope 設計を固めてから Worker mode 対応に着手する。

### 結論

`docs/refactoring.md` のリファクタリング内容は、全体として実施する価値がある。

不適切な方針は見当たらないが、現状との差分を踏まえると、Logging / Error Handling / Middleware は「新規追加」ではなく「残作業の具体化」に更新すべき。Worker mode 対応は影響範囲が大きいため、request scope の設計を先に文書化してから実装するのが適切。

---

## リファクタリング方針の最終結果

### 前半レビューの適切さ

前半のレビュー内容は技術的には正確であり、指摘された課題はすべて実際のコードで確認できる。

ただし、以下の点で実態との乖離がある。

- Logging / Error Handling のすべての項目が `[ ]` のまま列挙されているが、`JsonFileLogger`・`ExceptionHandlerInterface`・`RequireCmsSessionMiddleware` はすでに存在する。コードを正確に読んだ上でのレビューではなく、設計意図から導出した「あるべき姿」を列挙したものとなっており、実施済み項目の判定が甘い。
- `cms.auth` middleware は `[ ]` のままだが、`RequireCmsSessionMiddleware` として実装済みである。

### 後半評価の適切さ

「リファクタリング評価結果 2026-06-07」は正確である。実施済み項目の判定はコードと一致しており、Worker Mode の Csrf singleton リスクの指摘も「container を長寿命化した場合」という条件付きで正確である。優先順位の提案も妥当である。

ただし「Article::fromInput() / Page::fromInput() の入力正規化 helper 化は適切」という評価には留保が必要である。`Article` のコンストラクタはすでに ValueObject のみを受け取る純粋な設計であり、`fromInput()` は input 正規化のための static factory にすぎず Domain の業務ルールを侵していない。`fromInput()` を Application 側へ移すには `Input` の構造変更と Interactor のバリデーションロジックの書き直しを伴い、得られるのは配置の整合性のみである。このリファクタリングは実施しない。

### 最終方針

今回のリファクタリング対象は callback 系配列関数の置換に絞る。

`date()` / `strtotime()` helper、Architecture Test、`CurrentUser`、AuditLogger 本実装、Worker mode は今後の改善候補として妥当だが、いずれも設計判断または機能追加を含む。直近の目的である PHPStan 安定化・規約整理とは作業単位を分ける。

**callback 系配列関数の置換**

2つのパターンを分けて対処する。

- Persistence 層（row → Application Value DTO 変換）を優先する。`PdoArticleQuery`・`PdoTaxonomyQuery`・`PdoMediaQuery` など、DB row を DTO へ変換している `array_map` を `foreach` へ置き換える。型安全と PHPStan 安定化の観点で最も価値が高い。
- Controller 層（DTO → `toArray()` 変換）は次に対処する。Latte はオブジェクトのプロパティを直接参照できるため、`array_map(fn($x) => $x->toArray(), ...)` を廃止して DTO をそのままテンプレートに渡す形へ移行できる。
- container config の `$csv`（`array_map` + `array_filter`）は設定ファイル内の局所的な使用であり、最後に対処する。
