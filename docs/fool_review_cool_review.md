# fool_review 対応方針

`docs/fool_review.md` は、問題意識として拾えるものはあるが、現コードの前提を読み違えている箇所と、Framework / Kernel / App の責務分担を無視している箇所が混ざっている。

この文書では、各指摘をそのまま採用するのではなく、pera の思想に合わせて対応方針を整理する。

## 判断基準

対応方針は以下を基準にする。

- Framework は HTTP 実行基盤、値、差し込み口、安全な接続点を持つ。
- Kernel はプロジェクト標準の構成、環境ごとの wiring、Web 実行規約を持つ。
- Shared は複数 App が使うアプリケーション側の横断部品を持つ。
- App は業務要件と、その App 固有の運用判断を持つ。
- ワーカーで共有してよいのは、リクエスト間で不変かつリクエスト状態を持たないものだけ。
- 便利さのためにアプリケーション開発者の判断を奪わない。
- セキュリティ上の基本線は scaffold で安全側に倒す。

## 優先度一覧

| 指摘 | 方針 | 優先度 | 理由 |
|---|---|---:|---|
| S-1 CSRF middleware 化 | 採用 | 高 | Web form の基本防御であり、Controller 規約より middleware の方が漏れに強い |
| S-2 状態なし構成の worker reuse | 一部採用 | 中 | routes/middleware は既に概ね reuse 済み。Container 定義 cache は別設計が必要 |
| S-3 例外ハンドラ安全化 | scaffold 側で採用 | 中 | 初回リリース時の設定漏れに効くが、Framework core の既定変更は微妙 |
| S-4 CSP unsafe-inline 撤廃 | 採用 | 中 | 指摘箇所は誤りだが、`SecurityHeadersMiddleware` の課題として妥当 |
| A-1 routing cachedDispatcher | 原則却下 | 低 | worker では `Router` と dispatcher を既にプロパティ保持している |
| A-2 layout 共通 query cache | 採用 | 高 | render ごとの DB 3 往復は実効性能に効く |
| A-3 Container 定義 worker cache | 保留 | 低 | 雑にやると request scoped instance 混入リスクがある |
| A-4 middleware chain 事前構築 | 保留 | 低 | 効果が小さく、現状の単純さを崩すほどではない |
| B-1 class_exists fallback 廃止 | 保留 | 中 | autowire の便利さと明示性の trade-off。freeze と一緒に検討 |
| B-2 Container static scope | 説明追加 | 中 | Framework が勝手に scope を決める話ではない |
| B-3 Request input 分離 | 原則却下、追加 API は任意 | 低 | App 開発者が用途を理解する領域。横断処理では明示取得が有効 |
| B-4 CI | 採用 | 中 | `composer analyse` / `composer test` はあるので自動実行すべき |
| B-5 semver / BC policy | package 化時に採用 | 低 | 今の webmedia 内では早い |
| C 系微最適化 | 原則却下 | 低 | 現時点の主性能問題ではない |

## S-1: CSRF middleware 化

### 方針

採用する。

ただし、`Csrf` を Framework から外す必要はない。Framework は session-backed web form の基本 primitive として `Csrf` を持ってよい。中途半端なのは、CSRF primitive はあるのに検証が Controller 規約になっている点である。

### 理由

CSRF は Web service では必須級の防御であり、アプリケーション開発者に毎回 Controller で `validate()` を書かせるより、middleware pipeline で構造化した方が漏れに強い。

Framework が CSRF を持つことは「アプリケーション要件を奪う」ことではない。session-backed form を安全に作るための接続点を提供しているだけである。一方、どの App に適用するか、どの method/path を対象にするかは Kernel / Shared / App の構成判断である。

### 対応内容

- Framework に `CsrfMiddleware` を追加する。
- `CsrfMiddleware` は状態変更 method を対象にする。
  - 対象: `POST`, `PUT`, `PATCH`, `DELETE`
  - 除外: `GET`, `HEAD`, `OPTIONS`
- token は body の `_csrf` から読む。
- 不正 token は `403` または `419` 相当の response を返す。
- scaffold で生成する middleware config には、session-backed web app の標準として CSRF middleware を入れる。
- 既存 CMS / front の Controller から個別 `csrf->validate()` を段階的に撤去する。
- token 発行は現在どおり view/global provider 側に残す。

### 実装場所

- `src/Framework/Security/Csrf.php`: 維持
- `src/Framework/Security/CsrfMiddleware.php` または `src/Framework/Http/Middleware/CsrfMiddleware.php`: 追加候補
- `apps/shared/config/middleware.php` または scaffold 生成物: デフォルト組み込み
- `bin/scaffold-shared` / `bin/scaffold-app`: 生成物更新

### 注意点

API app や webhook app では CSRF が不要な場合がある。Framework core が常時強制するのではなく、scaffold と middleware config で安全側に倒す。

## S-2 / A-1: routing cache

### 方針

`cachedDispatcher` への切り替えは原則不要。

### 理由

レビューは「毎リクエスト route 正規表現を再構築している」と読んでいるが、現コードでは `Application` の constructor で `Router` を生成し、`Router` 内で dispatcher を `??=` で保持している。

worker mode では `Application` が生存するため、Router と dispatcher は実質的に worker lifetime で再利用される。つまり「ただ変数に入れておく」は既にほぼ達成している。

`FastRoute\cachedDispatcher` のファイルキャッシュは、route 変更時の invalidation、cache file の書き込み権限、deploy 時の扱いが増える。現在の routes 規模では、その複雑さに見合う効果は薄い。

### 対応内容

- 直近対応なし。
- 性能計測で route dispatch が実ボトルネックになった場合のみ再検討する。
- 再検討する場合も、まず現状の in-memory dispatcher reuse を benchmark で確認する。

## A-2: layout 共通 data cache

### 方針

採用する。

### 理由

front の `TemplateRenderer` global provider は render ごとに以下を読む。

- published page links
- site settings
- categories

これは低更新頻度の layout data であり、リクエストごとの DB 往復としては重い。routing や middleware closure より先に効く可能性が高い。

### 対応内容

- `CacheInterface` を使って layout global data を cache する。
- cache key は front layout 専用に分ける。
- TTL は短めから始める。
  - 例: 60 秒から 300 秒
- CMS の以下更新時に invalidation する。
  - site settings update
  - page publish/status update
  - category create/update/delete
- invalidation が面倒な初期段階では短 TTL だけでもよい。

### 実装場所

- `apps/front/config/container.php`
- `apps/shared/src/Cache`
- CMS の Setting / Page / Category 保存処理

## S-3: 例外ハンドラの既定安全化

### 方針

scaffold 側で採用する。

Framework core の default をいきなり変えるのは避ける。

### 理由

本番で未処理例外が起きた場合、詳細 stack trace や内部 message が外に出る構成は避けたい。特に初回リリース時は env、DB、migration、permission の設定漏れが起きやすい。そこで例外が裸で出ると、初回リリース時だけでも情報漏洩になる。

ただし、Framework core が `RethrowingExceptionHandler` を default にしていることにも理由はある。Framework は runtime の差し込み口を提供する層であり、どの HTML / JSON / log policy を返すかはアプリケーション方針で決めるべきだからである。

そのため、Framework の既定を変えるより、scaffold-kernel が生成する application config で安全側の handler を選ぶのが一番自然である。

### 対応内容

- `PlainTextExceptionHandler` は維持する。
- scaffold-kernel が生成する `apps/kernel/config/application.php` で、環境に応じて handler を選ぶ。
- 例:
  - `APP_DEBUG=true`: `RethrowingExceptionHandler`
  - それ以外: `PlainTextExceptionHandler`
- 将来的に project-specific handler を用意する場合は Kernel 側に置く。

### 実装場所

- `bin/scaffold-kernel`
- `apps/kernel/config/application.php`
- 必要なら `src/Framework/Http/ExceptionHandling/README.md`

### 注意点

開発時に例外が見えなくなると開発体験が悪い。必ず debug mode では再 throw できる構成にする。

## S-4: CSP unsafe-inline

### 方針

採用する。

ただしレビューの「Caddyfile」は誤りで、現状の実体は Shared の `SecurityHeadersMiddleware` と scaffold 生成物である。

### 理由

`unsafe-inline` は CSP の効果を大きく落とす。加えて、CDN 許可が残っていると asset 管理と CSP 方針がぶれる。

これは Framework core の責務ではなく、Shared / Kernel / App の security policy である。

### 対応内容

段階的に対応する。

1. テンプレート内の inline script / inline style を棚卸しする。
2. CDN 依存をローカル asset に寄せる。
3. `SecurityHeadersMiddleware` の CSP から不要な CDN 許可を外す。
4. inline が残る場合のみ nonce / hash を検討する。

最初から nonce に寄せるより、まず inline と CDN を減らす方が単純である。

### 実装場所

- `apps/shared/src/Security/Infrastructure/Http/SecurityHeadersMiddleware.php`
- `bin/scaffold-shared`
- `apps/*/templates`
- `public/assets`

## A-3: Container 定義の worker cache

### 方針

保留する。

### 理由

container config を毎リクエスト `require` しているのは事実だが、Container には複数の性質が混ざっている。

- bindings / factories: 定義であり、本来は不変
- instances: リクエスト固有の実体を含む
- singleton: Container instance 内で共有される実体
- workerSingleton: PHP worker lifetime で共有される実体

これを雑に worker cache すると、`Request`, `Session`, `Csrf`, user context, TemplateRenderer の closure などが前回 request の Container や instance を掴む事故が起きる。

pera の価値は、アプリケーション開発者が本筋と無関係な lifetime 管理を意識しなくて済むことにある。性能改善のために lifetime の罠を増やすなら本末転倒である。

### 対応内容

直近対応なし。

将来やる場合は、以下のように専用設計として扱う。

- Container を definition と request instances に分離する。
- worker に置くのは definition だけにする。
- request ごとに empty instances を持つ child container を作る。
- workerSingleton は definition cache とは別概念として維持する。
- request scoped service を worker cache に入れられないことをテストで保証する。

## A-4: middleware chain 事前構築

### 方針

保留する。

### 理由

middleware class list は `Application` constructor で読み込まれており、毎回 config file を読んでいるわけではない。毎リクエスト発生しているのは chain 用 closure / handler object の組み立てである。

これは確かに微小なコストだが、DB query や template render と比べると優先度は低い。構造 cache を入れると、middleware instance の lifetime と Container 解決の扱いが複雑になる。

### 対応内容

直近対応なし。middleware 数が増え、計測で問題になった場合だけ検討する。

## B-1: class_exists fallback

### 方針

保留する。

### 理由

暗黙 autowire は事故要因になり得る。一方で、この Framework の小ささと使いやすさにも効いている。

`has()` が `class_exists()` を true にする点は PSR-11 的にやや広いが、現時点で突然廃止すると Controller / Middleware / service 解決の書き味がかなり変わる。

### 対応内容

直近では廃止しない。

検討するなら Container freeze と合わせて以下を考える。

- 登録期間と実行期間を分ける。
- production で未登録 autowire を禁止できる option を作る。
- scaffold では明示 bind を増やす。
- `has()` の意味を README に明記する。

## B-2: Container static scope

### 方針

説明追加で対応する。

Framework が app scope を勝手に導入する必要はない。

### 理由

`workerSingleton()` は PHP worker process 全体で共有する明示 API である。front と cms で同じ PDO を共有するか、別 PDO にするかはアプリケーション構成の判断である。

Framework が「front/cms は別 scope にする」と決めると、逆に単一 DB / 単一 connection を共有したいプロジェクトの判断を奪う。

必要なら開発者が id を分ければよい。

例:

```php
$container->workerSingleton(id: 'cms.pdo', factory: ...);
$container->workerSingleton(id: 'front.pdo', factory: ...);
```

または interface / class を App ごとに分けて bind すればよい。

### 対応内容

- `workerSingleton()` の phpdoc / README に、Container instance 単位ではなく PHP worker process 単位で共有されることを明記する。
- `Request`, `Session`, `Csrf`, user context, auth state, per-request cache には使わないことを明記する。
- 複数 App で別実体が必要な場合は id を分けることを明記する。
- `reflectionCache` は状態を持たない performance cache であると明記する。

### 実装場所

- `src/Framework/Container/Container.php`
- `src/Framework/Container/README.md`

## B-3: Request::input() body/query 暗黙 merge

### 方針

暗黙 merge の廃止はしない。

必要なら `bodyInput()` / `queryInput()` を追加する程度に留める。

### 理由

アプリケーション開発者は、GET search なら query、POST form なら body を読むことを理解している前提でよい。そこまで Framework が強制すると、便利さより儀式が増える。

ただし、横断処理では入力元を固定した方がよい。たとえば CSRF middleware は `_csrf` を body から読むべきで、query fallback は不要である。

つまり、問題は App Controller 全体に入力元明示を強制することではなく、Framework / Shared の横断処理が曖昧な `input()` に依存しないことである。

### 対応内容

- 既存 `input()` は維持する。
- 破壊的変更はしない。
- 任意で以下を追加する。
  - `bodyInput(string $key, string $default = ''): string`
  - `queryInput(string $key, string $default = ''): string`
- CSRF middleware など横断処理では `bodyInput()` または body 直接参照を使う。
- README に `input()` は body 優先、query fallback の convenience API であることを明記する。

## B-4: CI

### 方針

採用する。

### 理由

`composer analyse` と `composer test` は用意されているが、CI がなければ継続的な保証にならない。

### 対応内容

- GitHub Actions を追加する。
- Docker Compose 経由で実行できるようにする。
- 最低限以下を実行する。
  - `composer analyse`
  - `composer test`

### 注意点

このリポジトリの host environment には PHP / Composer がないため、CI とローカル検証手順は Docker Compose 前提で揃える。

## B-5: semver / BC policy

### 方針

Framework package 化時に採用する。

### 理由

公開 package として他者が依存する段階では必要。ただし、現時点の webmedia 内部開発では優先度が低い。

### 対応内容

- package 分離時に public API を定義する。
- `src/Framework` の public contract と internal implementation を分ける。
- 破壊的変更の扱いを README に書く。

## C 系指摘

### 方針

原則として対応しない。

### 理由

`fromGlobals` の server copy、`Response::send()` の header validation、`matchDefinition` の線形探索、`dirname()` 再計算、`singleton` 二重 guard は、現時点の性能や堅牢性の本丸ではない。

ここを詰めるより、layout data cache、CSRF middleware、CSP 整理、CI の方が価値が高い。

## 実施順

1. CSRF middleware を追加し、scaffold / middleware config に組み込む。
2. front layout global data cache を入れる。
3. scaffold-kernel の例外ハンドラを debug mode に応じて安全側にする。
4. `workerSingleton()` の説明を強化する。
5. CSP から CDN / inline 依存を減らす。
6. CI を追加する。
7. 必要なら `bodyInput()` / `queryInput()` を追加する。

## 採用しない大きな主張

### routing cachedDispatcher は本丸ではない

worker mode では Router と dispatcher が既に memory 上に残る。ファイル cache を導入する前に、現状 reuse の効果と route 数に対する実測を見るべきである。

### Container 定義 cache は単純な変数 cache ではない

routes や middleware class list と違い、Container は lifetime の異なるものを持つ。雑な cache は pera の「リクエスト状態を漏らさない」という強みを壊す。

### Request input 分離を強制しない

入力元を理解するのは App 開発者の通常責務である。Framework は便利な API を提供し、横断処理では必要に応じて明示 API を使えばよい。

## 結論

`fool_review.md` から拾うべきなのは、主に以下である。

- CSRF を Controller 規約から middleware 構造へ移す。
- layout 共通 DB query を cache する。
- scaffold の本番例外応答を安全側にする。
- CSP の `unsafe-inline` / CDN 依存を整理する。
- workerSingleton の共有 scope を明文化する。
- CI を入れる。

一方、routing cache、middleware chain cache、Container definition cache、input merge 廃止は、現時点では過剰または優先度が低い。pera の価値は、アプリケーション開発者が lifetime や framework 都合の儀式を意識せずに、本筋の実装へ集中できることにある。その価値を壊す最適化は採用しない。
