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
- [x] callback 系配列関数の置換対象を Persistence 層の型境界に限定する
  - [x] Persistence 層の row → DTO 変換で使っていた `array_map()` を `foreach` へ置き換える
  - [x] Controller 層の DTO → `toArray()` 変換で使っている `array_map()` は解消しない
  - [x] container config の CSV 正規化で使っている `array_map()` / `array_filter()` は解消しない
  - [x] seed の初期データ生成で使っている `array_map()` は解消しない
  - [x] `array_filter()` / `array_reduce()` / `array_walk()` の全廃は独立した品質目標にしない
- [ ] 時刻取得・日付変換の扱いをアプリケーション層で整理する
  - [ ] Framework 側に日付 helper は追加しない
  - [ ] PSR-20 `ClockInterface` は、現在時刻を固定したいユースケースが明確になった場合のみ検討する
  - [ ] `apps/{cms,front,shared}` 配下の `date()` / `strtotime()` 整理はアプリケーション開発者の判断に委ねる
- [x] `Article::fromInput()` / `Page::fromInput()` の移動は実施しない
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

## 最終責任者からの判定 2026-06-08

上記の「Controller 層（DTO → `toArray()` 変換）は次に対処する」というレビュー内容は不適切であり、実施してはならない。

Controller 層の `array_map(fn($x) => $x->toArray(), ...)` は、Application Output / Value DTO をテンプレート用の配列へ変換する明示的な境界である。これを廃止して DTO を Latte テンプレートへ直接渡すと、テンプレートが DTO の public property や accessor の構造へ直接依存し、Controller が担っている view data 整形の責務が曖昧になる。

この変更には、型安全性・保守性・実行時挙動のいずれにも十分な利益がない。得られるのは「callback 全廃」という形式上の整合だけであり、既存設計の境界を弱めるため、リファクタリングとして不適切である。

したがって、以下は明確に禁止する。

- Controller 層の `toArray()` 変換を廃止して DTO を Latte テンプレートへ直接渡すこと。
- `array_map()` を消すこと自体を目的に、Controller / config / seed の局所的で明確な配列処理を書き換えること。
- 「callback 系配列関数の全廃」を独立した品質目標として扱うこと。

今回実施済みの Persistence 層の置換は、DB row の `array<string, mixed>` を DTO へ変換する型境界に限定したため妥当である。今後の作業では、この範囲を超えて callback 置換を進めない。

## アプリケーション層の局所実装に関する判定 2026-06-11

Framework 側にアプリケーション都合の日付 helper は追加しない。

Framework は特定アプリケーションの DB 日付フォーマット、表示フォーマット、保存時刻生成の都合を知らない層である。`date()` / `strtotime()` の呼び出しを減らす目的で Framework に helper を置くと、Framework がアプリケーション固有の表現や永続化方針を背負うことになるため不適切である。

Framework 側で時刻取得の抽象が必要になった場合は、独自 helper ではなく PSR-20 `Psr\Clock\ClockInterface` を採用候補にする。これは「現在時刻を取得する依存」を差し替えるための抽象であり、日付文字列の整形 helper ではない。DB 保存形式や画面表示形式の正規化は `apps/{cms,front,shared}` 側で必要に応じて設計する。

また、`apps/{cms,front}` 配下に残る Controller の `array_map(fn ($x) => $x->toArray(), ...)`、container config の CSV 正規化、seed の初期データ生成は、いずれもアプリケーション層の局所的な実装判断である。これらを Framework 規約として一律に置換する必要はない。

特に Controller 層の `toArray()` 変換は Application Output / Value DTO を view data へ変換する境界であり、Latte 側を DTO 構造へ寄せる変更は行わない。テンプレートに手を入れて callback を消すことは目的と利益が釣り合わず、既存の責務分担を弱める。

したがって、今後の対応方針は以下とする。

- Framework には日付 helper を追加しない。
- PSR-20 `ClockInterface` は、現在時刻を固定したいユースケースが明確になった場合のみ検討する。
- apps 配下の `date()` / `strtotime()`、callback 系配列関数、`toArray()` 変換はアプリケーション開発者の判断に委ねる。
- callback 全廃や helper 化を目的に Latte、Controller、config、seed を変更しない。

## 日付 helper 化指摘への返答 2026-06-11

`date()` / `strtotime()` の散在を「日付 helper に閉じ込める」形で解消する指摘は、このプロジェクトでは不適切である。

理由は、問題の中心が「関数呼び出しの重複」ではなく「その日時がどの層でどの意味を持つ値なのか」にあるためである。helper は呼び出し場所を減らせるが、公開日時、予約公開日時、作成日時、更新日時、表示用日付、DB 保存用 timestamp といった意味の違いを表現しない。意味の違う日時を同じ helper に集めると、依存方向は見えにくくなり、Domain / UseCase / Infrastructure の境界も曖昧になる。

業務上の意味を持つ日時は、必要になった時点で Domain または UseCase 側の Value として表現する。たとえば公開判定に関わる日時、予約公開の条件、期限切れ判定などは、単なる文字列整形ではなく業務ルールを持つ値として扱う方がよい。画面表示用の文字列は Application Output 側、DB 文字列との相互変換は Infrastructure 境界で扱う。

PSR-20 `Psr\Clock\ClockInterface` も現時点で必須ではない。PSR-20 は日付操作ライブラリではなく、現在時刻を取得する依存を差し替えるための抽象である。公開予約、期限切れ、監査ログなどで `now()` を固定したテストが必要になった場合に採用を検討すればよい。`created_at` / `updated_at` の保存や表示整形だけを理由に導入する必要はない。

したがって、今後の判断は以下とする。

- 日付 helper を作ること自体をリファクタリング目標にしない。
- Carbon 互換のような多機能な日時ユーティリティは作らない。
- 業務上の意味がある日時は Domain / UseCase の Value として表現する。
- DB 保存形式や表示形式の変換は Infrastructure または Application Output の境界で扱う。
- PSR-20 は、現在時刻の差し替えが必要な具体的ユースケースが出た場合に限り検討する。

---

## scaffold-app minimal template の見直し 2026-06-08

### 何をするか

`bin/scaffold-app` の template 構成を整理する。

現在の `--template=minimal` は、Kernel に接続するための config 3点と `src/.gitkeep` だけを生成する。これは極薄の構成としては有用だが、`minimal` という名前から期待される「最小限だが動くアプリ」ではない。

今後は以下の構成へ変更する。

- `--template=bare`
  - 現在の `minimal` と同じ内容を生成する。
  - `config/routes.php`
  - `config/container.php`
  - `config/middleware.php`
  - `src/.gitkeep`
  - route、Controller、template renderer、view template は生成しない。
- `--template=minimal`
  - 動く最小アプリを生成する。
  - `bare` の生成物に加えて、少なくとも以下を生成する。
    - `src/Infrastructure/Http/Controller/Index/Controller.php`
    - `templates/index.latte`
    - `config/routes.php` に `GET <path-prefix>` の route
    - `config/container.php` に `TemplateRenderer::class` の binding
  - `TemplateRenderer` の具象実装には、既存の `App\Shared\View\Infrastructure\LatteTemplateRenderer` を使う。
  - template directory は `$root . '/apps/{name}/templates'`、cache directory は `$root . '/storage/cache/{name}'` を使う。
- `--template=layered`
  - 現在の layered starter を維持する。

`--template` を省略した場合の default は、原則として新しい `minimal` とする。つまり、初回利用者が scaffold 後に ApplicationDefinition と composer autoload を追加すれば、すぐにブラウザで表示を確認できる状態にする。

### なぜするか

`minimal` という名前は、利用者に「余計な層はないが、HTTP request から Controller、view rendering までの基本導線はある」という期待を持たせる。現在の `minimal` は config だけで、アクセス可能な画面も Controller 例もないため、初回利用時に何を足せばよいか分かりにくい。

一方で、template engine を決めたくない場合や、route / Controller / view をすべて自分で作りたい場合には、現在の極薄構成にも価値がある。そのため削除せず、より正確な名前である `bare` として残す。

Latte を `minimal` に含めることは、Framework core が Latte に依存することを意味しない。このリポジトリでは `scaffold-shared` が `TemplateRenderer` interface の標準実装として Latte renderer を生成しており、Framework core は引き続き `TemplateRenderer` interface のみに依存する。Latte は scaffold の標準 view 実装であり、別の template engine を使いたい場合は `bare` を選ぶか、`minimal` 生成後に container の binding と template を差し替えればよい。

### 実装時の注意

- `bin/scaffold-app` の usage と validation を `bare|minimal|layered` に更新する。
- `bin/README.md` と必要に応じて `apps/kernel/README.md` / `src/Framework/README.md` の説明を更新する。
- 既存の `apps/minimal` は scaffold 生成物ではなく、試作用の参考実装である。実装の参考にはできるが、「既に scaffold で作れている」と誤認しないこと。
- `apps/minimal/config/container.php` には試作用の `PDO::class` sqlite binding があるが、新しい `minimal` scaffold にそれをそのまま含める必要はない。IndexController が DB を使わないなら、不要な PDO binding は生成しない。
- `GET <path-prefix>` の route は、`path-prefix` が `/` の場合は `/`、それ以外は末尾 slash を除いた prefix そのものにする。
- 生成後メッセージでは、`bare` は「config only」、`minimal` は「IndexController + Latte view」、`layered` は「Application / Domain / Infrastructure starter」であることを明示する。
- 互換性上、これまで `--template=minimal` を使って config only を期待していた自動化は `--template=bare` へ変更する必要がある。README にその移行先を明記する。

---

## worker lifetime service の整理 2026-06-11

`Container::persistent()` は名称が不適切であるため、`Container::workerSingleton()` に改名する。

この API は connection pool ではなく、現在の PHP worker のメモリに instance を保持する lifetime 指定である。Framework は `PDO` などの具体的な永続対象を知らず、Container は lifetime 管理だけを扱う。

ただし request を跨いで同一 instance が使われるため、`Request`、`Session`、`Csrf`、現在ログイン中のユーザー、flash message、validation error、request id / request context には使ってはならない。DB 接続のように worker 単位の再利用が妥当な service に限定して、アプリケーション composition 側で明示的に選択する。
