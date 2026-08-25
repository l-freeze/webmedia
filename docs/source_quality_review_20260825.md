# Framework / Kernel / Shared ソース品質レビュー

## 位置づけ

- 実施日: 2026-08-25
- 最終更新: 2026-08-26
- 対象:
  - Framework: `packages/framework/src`、`packages/framework/bin`、公開 package 文書、対応 test
  - Kernel: `apps/kernel`、Kernel scaffold template、対応 test
  - Shared: `apps/shared`、Shared を利用する composition / middleware、対応 test
- 観点: 正しさ、失敗時の振る舞い、境界の一貫性、セキュリティ、保守時に誤りを検出できるか

本レビューは `/home/yamamoto/my-tmp/review-points/review-point.md` と同 directory の guardrail に従う。分類は次のように正本の用語へ対応させる。

- 確定所見 = `finding`: 現行source / contractに違反があり、修正対象として成立するもの。
- 検証項目 = `verification item`: 問題候補はあるが、現行defectと断定するための事実が不足しているもの。
- 要判断 = `decision required`: 複数の合理的な選択肢があり、利用者または設計所有者の判断が必要なもの。
- 非所見 = `non-issue`: 現行の思想・保証・利用条件では問題として扱わないもの。
- 既知改善 = `planned improvement`: 正本文書ですでに方向が決まり、未実装として追跡されているもの。新たなfindingや要判断には数えない。

findingの確信度は`confirmed`、`probable`、`unverified`で表す。source上の成立、外部入力からの到達可能性、発生頻度は確信度と混ぜず、必要な箇所で別に記載する。既存のアーキテクチャ規約と設計判断を先に読み、薄いFramework、composition rootとしてのKernel、アプリ横断InfrastructureとしてのSharedという所有境界を前提にしている。一般的なFrameworkや抽象化階層へ寄せること自体は改善理由にしていない。

## 実行条件と確信度

ソース、設定、既存test、配布template、vendor内のDotenv実装を静的に照合した。PHP / Composerはhostで実行せずDocker Composeを使う規約だが、この環境ではWindows側Docker CLIへのpathだけが見え、WSL integrationと利用可能なdaemonがなかったため、PHPUnit、PHPStan、再現用PHP scriptは実行できていない。

`confirmed`は現行sourceとcontractから条件と結果を追跡できたという意味であり、この実行環境で動的再現済みという意味ではない。外部proxyやstagingの公開条件が必要な項目は断定せず、検証項目へ分けた。

## 文書化とソース修正の切り分け

README は、利用者と実装者が守る contract、middleware の合成順、異常時の方針を共有するために必要である。一方、正しい設定だけを前提にしなければ安全性や接続先が変わる箇所では、README は入力検証や失敗検出の代わりにならない。

本レビューの Medium 4件は、いずれも現在の設計思想を変更する提案ではなく、その思想が成立するための不変条件を source で保証する提案である。推奨対応は次のように分ける。

| 所見 | source で必要な保証 | 併せて更新する文書 |
| --- | --- | --- |
| FW-01 Session | native API の失敗を検出し、成功した state として扱わない | Framework Security README に失敗時の例外 contract を記載 |
| K-01 DB environment | Kernelが所有する一つの規則でDotenvとprocess environmentを解決する | Kernel READMEと`.env.example`に優先順位・空文字・既定DBを記載 |
| K-02 ApplicationDefinition | name / prefixの一意性とcanonical prefixを起動時に検証する | Kernel READMEにdefinitionの不変条件を記載 |
| SH-01 JSON log | 共通writerはencodingを安全化して保存失敗を明示的な例外で返し、CMSアプリケーションがAudit保存失敗時の継続方針を選ぶ | Shared / CMS Audit文書にencoding、例外、アプリケーション側の継続方針を記載 |

README の更新は source 修正を補強する受け入れ条件であり、source 修正の代替にはしない。

## 結果要約

| 対象 | Medium | Low | 別分類 |
| --- | ---: | ---: | --- |
| Framework | 1 | 2 | 既知改善 1、要判断 1 |
| Kernel | 2 | 1 | 要判断 1 |
| Shared | 1 | 0 | 検証項目 2 |
| 合計 | 4 | 3 | 既知改善 1、要判断 2、検証項目 2 |

優先度は次の順が妥当である。

1. Kernel の `.env` と DB 接続設定の読み取りを一致させる。
2. Kernel の application definition を起動時に検証する。
3. native session 操作の失敗を成功扱いしない。
4. JSON encodingを安全化し、保存失敗を明示的に通知したうえで、CMS側の方針としてAudit保存失敗を完了済みrequestから分離する。
5. Lowのprotocol / scaffold / 文書所見を、各所有testと同じwork unitで直す。

---

## Framework

### 良い点

- HTTP、routing、DI、middleware、session、process 実行の public surface が小さく、webmedia 固有の業務知識を package 内へ持ち込んでいない。
- Kernel が container を request ごとに組み直し、Framework の `Session` が遅延開始する構成は、worker 内で request state を共有しないという規約と一致している。
- scaffold は transaction と rollback を持ち、生成 template、consumer Composer、標準 Kernel 登録を package test / integration test で広く固定している。
- `ProcessRunner`、redirect header、malformed URI、container lifetime など、外部境界での失敗を明示的に扱う実装が多い。

### FW-01: native session 操作の失敗が lifecycle 上の成功として進む

- 分類: 確定所見
- 重要度: Medium
- 確信度: confirmed
- 場所: `packages/framework/src/Security/NativeSessionRuntime.php:20-54,85-105`

`session_start()`、`session_regenerate_id(true)`、`session_write_close()`、`session_destroy()` の真偽値を確認していない。`Session` と Kernel は各 method が正常終了したものとして後続処理を続けるため、session 保存先の障害、header 送信後の開始、ID 再生成失敗などが warning だけで流れると、認証・CSRF・flash の状態が「変更できたように見えるが保存されない」状態になる。特にログイン時の ID 再生成失敗を検出しないのは、session fixation 対策の成立条件を曖昧にする。

再現条件は、書き込み不能な session save path などを設定して `start()` / `close()` を呼ぶ、または active session で再生成が失敗する条件を作ることである。現行 test は cookie 属性と正常系 Session 操作を確認しているが、native API が `false` を返す経路は固定していない。

最小修正は、各native APIの戻り値と必要な`session_status()`を確認し、状態を使用・更新済みとして扱う前に専用の`RuntimeException`を送出すること。testは隔離processか差し替え可能な小さなnative-call境界を使い、開始、再生成、close、destroyの各失敗を確認する。session backendの汎用abstractionを増やす必要はない。

これは「Session backend は通常成功する」という運用前提を README に加えれば済む問題ではない。認証時の ID 再生成や state 保存は、成功したかどうかがrequestの意味を変える境界だからである。Framework Security README には、native runtime の失敗をrequest失敗として扱い、Controllerやmiddlewareが握りつぶさない contract を追記する。source はその contract を戻り値検査で保証する。

### FW-02: 405 response に `Allow` header がない

- 分類: 確定所見
- 重要度: Low
- 確信度: confirmed
- 場所: `packages/framework/src/Routing/Router.php:42-52`

FastRoute の `METHOD_NOT_ALLOWED` result には許可 method 一覧が含まれるが、Router はそれを捨て、status と本文だけの response を返す。405 は対象 resource が受け付ける method を `Allow` で通知する HTTP response なので、client、API tooling、protocol test が利用可能 method を判断できない。

既存 `RouterTest` は dispatch、404、route metadata を確認するが 405 を扱わない。最小修正は result の allowed methods を `Allow: GET, POST` のように response へ渡し、GET route に POST した場合の status と header を test すること。

### FW-03: `scaffold-app` が固定用途名 `kernel` / `shared` を許可する

- 分類: 確定所見
- 重要度: Low
- 確信度: confirmed
- 場所: `packages/framework/bin/scaffold-app:39-64`、`packages/framework/README.md:113-124`

公開 contract では `apps/kernel` と `apps/shared` は固定 path だが、app 名の検証は文字種だけで、`kernel` と `shared` を拒否しない。Framework-only の空 project で `scaffold-app kernel / --template=bare` を先に実行すると通常 app を固定 Kernel path に生成でき、その後の `scaffold-kernel` を衝突させる。同様に bare app は Shared 未生成でも `apps/shared` を占有できる。

最小修正は、filesystem transactionを始める前に予約名を拒否すること。testは両方の名前について非zero終了、`apps/{name}`・Composer・Kernel configが未変更であることを確認する。公開手順の正本である`packages/framework/INSTALLATION.md`にも、`kernel`と`shared`が予約名であることを追記する。

### Framework の既知改善と要判断

#### P-FW-01: forwarded protocol の trust boundary

- 分類: 既知改善（planned improvement）
- 状態: 改善方向は決定済み、未実装
- 場所: `packages/framework/src/Http/Request.php:91-98`、`docs/plan.md:54`
- 実施期限: productionのproxy / load balancer topologyを確定する前

`Request::isHttps()` は送信元を限定せず `HTTP_X_FORWARDED_PROTO=https` を信用し、その結果を session cookie の Secure 判定に使う。外側の proxy が header を必ず削除・再設定する構成なら成立する一方、app server へ直接到達できる構成では client 入力になる。現行 repository だけでは production の到達経路を確定できないため、脆弱性とは断定しない。

`docs/plan.md`は、直接TLSの判定とtrusted proxy由来の判定を暗黙header trustから分離する方向をすでに定めている。したがって未決の選択肢として扱わず、その方向でsourceを修正する。proxy側でheaderを上書きし、app serverへの直接到達を閉じるdeployment contractは、このsource修正を置き換える案ではなく、併せて固定する受け入れ条件とする。

#### D-FW-02: 明示された worker runtime の silent fallback

- 分類: 要判断（decision required）
- 場所: `packages/framework/src/Runtime/RuntimeFactory.php:17-43`、`docs/plan.md:55`
- 決定期限: Framework の初回安定 release 前

未知の `APP_RUNTIME`、または `frankenphp-worker` を指定したのに extension function がない場合も Classic runtime へ落ちる。開発時の可用性を優先する contract なら現状は妥当だが、worker 前提の Caddy 設定で構成ミスを隠すと restart / request 処理の挙動を診断しにくい。

推奨は、値を省略した場合だけ Classic を既定にし、明示値が未知または利用不能なら起動時に失敗させること。fallback を維持するなら、その contract と warning の出力先を公開 API 文書で固定する。

### Framework で問題としなかった点

- Kernel が app container を request ごとに `require` することは、worker 安全性のための明示された設計であり、singleton container への一般化は推奨しない。
- Framework 固有の小さな `Request` / `Response` / middleware API は意図した public surface であり、PSR-7 等への置換自体を品質改善とはみなさない。
- route metadata 取得と dispatch の二重 match は既に plan にある改善候補だが、現時点で correctness の破綻や計測済み bottleneck は確認できないため所見に数えない。

---

## Kernel

### 良い点

- Kernel は environment 初期化、app definition、request-scope composition、runtime entrypoint に責務を絞っている。
- app container を request ごとに作り、Router と middleware の不変設定だけを Application lifetime に保持する構成は worker の state isolation と整合する。
- malformed request と exception handling の境界が Application に集約され、現行 Kernel と配布 template の drift test もある。

### K-01: `.env` で読み込んだ DB 設定を `ConnectionFactory` が参照しない

- 分類: 確定所見
- 重要度: Medium
- 確信度: confirmed
- 場所: `apps/kernel/config/application.php:14-23`、`apps/kernel/src/Database/Infrastructure/ConnectionFactory.php:13-24`、`.env.example:9-13`
- 補強根拠: `vendor/vlucas/phpdotenv/src/Dotenv.php:148-152`、`vendor/vlucas/phpdotenv/src/Repository/RepositoryBuilder.php:25-28,91-95`

Kernel は `Dotenv::createImmutable()->safeLoad()` で `.env` を `$_ENV` / `$_SERVER` adapter へロードする。一方、DB factory は `DB_DSN` を含む全 DB 値を `getenv()` だけから読む。`createImmutable()` は putenv adapter を使わないため、OS process environment に DB 値がなく `.env` にだけ値がある構成では、読み込んだ値が DB 接続へ届かず `db:5432/webmedia` と既定 credential が使われる。

Docker Compose は DB 値を process environment として注入するため、通常の Compose test ではこの不一致が隠れる。再現は process の `DB_*` を未設定にし、`.env` に識別可能な `DB_DSN` 等を置いて application config を読み込んだ後、factory が作る接続先を確認することで行える。さらに `.env.example` は `DB_PORT=3306` だが、DSN 未指定時の factory と Compose の DB は PostgreSQL / 5432 である。reader だけを直すと `.env.example` の値が届くようになり、次に port 不一致が表面化する。

実装前に、process environmentと`.env`が競合した場合の優先順位、未設定と空文字の扱いをKernelのcontractとして明示する。そのうえで、`$_ENV`、`$_SERVER`、`getenv()`を同じ規則で読む小さなKernel-owned readerを一つだけ正本にし、Session設定とDB設定から利用する。DB factory内だけの局所readerを増やすとKernel内で規則が再び分裂するため避ける。併せてPostgreSQLを既定とする`.env.example`のportを5432に直す。testはprocess environmentだけ、`.env`だけ、競合時の優先順位、空文字、未設定時既定値を分ける。

Kernel README はすでに「Kernel が `.env` を任意ロードする」と説明しているため、実装に合わせて「DB は process environment だけを使う」と書き換えるのは、scaffold と `.env.example` の存在理由を損なう。ここでは README ではなく reader を直すのが設計に沿う。README には、process environment と `.env` の優先順位、未設定時に PostgreSQL / 5432 を使うことを追記し、source と利用者向け contract を一致させる。

### K-02: application definition の一意性と canonical prefix を検証していない

- 分類: 確定所見
- 重要度: Medium
- 確信度: confirmed
- 場所: `apps/kernel/src/Application.php:37-48,82-111,166-177`、`apps/kernel/src/ApplicationDefinition.php:11-24`

Router と middleware は definition の `name` を key に保持するが、request が使う container は path prefix で選んだ definition から読み込む。name が重複すると map の後勝ちにより、ある prefix / container に別 definition の Router と middleware が組み合わさる。たとえば同じ name で公開 prefix と管理 prefix を登録すると、選択された container に別側の middleware が適用され得る。

また、同じ prefix の別 name は入力順に依存した曖昧な定義になり、`/admin/` のような末尾 slash 付き prefix は constructor を通るが、`/admin/x` に対する比較が `/admin//` となり root app へ fall through する。標準 config は一意で scaffold は prefix を正規化するため現在の既定構成は保護されているが、consumer-owned Kernel の手編集も正式な経路なので起動時に拒否すべき構成エラーである。

最小修正は route / middleware file を読み込む前に、name の一意性、prefix の一意性、prefix が `/` または末尾 slash なしの canonical path であることを検証すること。test は重複 name、重複 prefix、末尾 slash が constructor 段階で失敗し、異なる app の container / Router を組み合わせないことを固定する。現行 Kernel と Framework の Kernel template は drift test 対象なので同じ work unit で同期する。

文書側は`apps/kernel/README.md`へdefinitionのname / prefixが一意であることとcanonical prefix形式を記載する。middleware layer間の優先方針は、このsource defectとは成立条件も修正境界も異なるため、本所見へ混ぜない。

### K-03: Kernel README の scaffold 登録 contract が実装と逆

- 分類: 確定所見
- 重要度: Low
- 確信度: confirmed
- 場所: `apps/kernel/README.md:65-82`、対照 `packages/framework/README.md:122-126`

Kernel READMEは`scaffold-app`が`apps/kernel/config/application.php`を自動編集しないと説明するが、現行実装は未変更の標準Kernelのmarker内へ`ApplicationDefinition`を自動登録し、consumer-owned custom Kernelのときだけ手動案内を出す。主な影響は、利用者へ不要な手作業と重複設定を案内する文書contract違反である。同一fieldのdefinitionをそのまま重複させただけなら直ちに別Container / Routerが混ざるとは限らないが、後から片方だけ編集すればK-02の不正構成へつながる。

最小修正は README を標準 Kernel / custom Kernel の二経路へ合わせること。scaffold contract の正本を `packages/framework/INSTALLATION.md` に置き、Kernel README は重複説明を短くして参照する形でよい。

### Kernel の要判断

#### D-K-01: middleware layer間の合成・競合方針

- 分類: 要判断（decision required）
- 場所: `packages/framework/src/Http/Middleware/MiddlewarePipeline.php:23-40`、`apps/shared/config/middleware.php`、各Appの`config/middleware.php`
- 判断期限: Kernel全体のglobal middlewareを追加する前、またはlayer間で同じresponse値を設定する前

現行pipelineはlayer名や数値優先度を解決せず、渡された配列をstackとして実行する。配列の先頭からrequest側へ入り、Controllerから返るresponseは逆順に戻り、先頭側のmiddlewareは後続を呼ばず短絡できる。現行App configはShared配列を先に展開し、その後へApp固有middlewareを置くため、request側はSharedからApp、response側はAppからSharedの順になる。Kernel独自のglobal middleware listは現在存在しない。

会話で提示された「Application > Shared > Kernel」は、同じresponse値が競合したときの所有方針としては合理的だが、正本の`docs/decisions.md`や`docs/webmedia_architecture_rules.md`ではまだ決定されていない。また、request側の短絡順序まで単純な優先度で表すことはできない。

採用する場合は、response競合では外側のmiddlewareが内側の明示値を上書きしないこと、request側は認証・CSRF・rate limit・例外処理ごとに順序と短絡条件を定めることを`docs/decisions.md`とarchitecture規約へ記録する。採用しない場合はglobal hierarchyを設けず、各Appのmiddleware listが明示順を所有する。どちらの場合も、Framework文書にはproject固有layer名を持ち込まず、`packages/framework/src/Http/Middleware/README.md`へrequest順、response逆順、短絡という汎用pipeline contractだけを追記する。

### Kernel で問題としなかった点

- production exception handler、request ID、logger の標準合成は `docs/plan.md` に残る明示的な未決事項であり、現在の `RethrowingExceptionHandler` を独立した source defect には数えない。
- longest-prefix の単純な app selection は現在の app composition に十分であり、汎用 host router や module registry の導入は推奨しない。
- Kernel が DB 接続の生成だけを所有し、Repository / SQL を持たない境界は維持すべきである。

---

## Shared

### 良い点

- Shared は cache、logging、audit、security log、view、clock、HTTP security、PDO helper など複数 app が実際に使う Infrastructure に絞られ、業務 entity や巨大な utility 集積にはなっていない。
- Application Port は各 app 側が所有し、Shared は adapter を提供する構造が概ね守られている。
- Audit / SecurityLog / ApplicationLog は用途別の record を持ち、EventBus の同期性も隠さず明示している。
- Markdown、HTML、rich-text の表示境界は allowlist と sanitizer を持ち、単なる共通 helper 化ではなく安全な出力責務として置かれている。

### SH-01: JSONL writer の失敗が完了済み request まで失敗させる

- 分類: 確定所見
- 重要度: Medium
- 確信度: confirmed
- 場所: `apps/shared/src/Logging/Infrastructure/JsonLineFileWriter.php:19-35`、`apps/shared/src/Logging/Infrastructure/JsonFileLogger.php:22-32,59-89`、`apps/shared/src/Audit/Infrastructure/JsonFileAuditLogger.php:20-37`、`apps/shared/src/SecurityLog/Infrastructure/JsonFileSecurityLogger.php:20-33`
- 到達経路例: `apps/cms/src/Presentation/Http/Middleware/AuditMiddleware.php:40-76,117-128`
- 既存 contract: `apps/cms/src/Presentation/Http/Middleware/AuditMiddleware.README.md:40-48`

共通 writer は `JSON_THROW_ON_ERROR` で record 全体をencodeし、不正 UTF-8 が一つでもあると `RuntimeException` にする。application logger のnormalizeもstringをそのまま残し、audit / security loggerはrequestのUser-Agent、request ID、metadataを直接recordに含める。directory作成やfile appendの失敗も同じく例外になり、CMSアプリケーション側にrequest境界から切り離す処理はない。

`AuditMiddleware` は状態変更 handler が response を返した後に同期 EventBus で記録するため、保存が完了した後に log encode が失敗すると、client には 500 が返り、監査 record は欠落し、操作結果が曖昧になる。middleware 文書も、実運用の AuditLogger は監査保存失敗だけで完了済み操作の response を壊さないことを標準としているため、現行 JSON logger はその contract を満たさない。`"\xFF"` を user agent または metadata に入れた Request を middleware / logger test へ渡せば source-level の失敗条件を再現できる。source上の成立は確定している一方、実際の外部 request から不正 byte が PHP server 変数まで到達する頻度と経路は、Caddy / upstream proxy ごとの確認事項である。

最小修正は二段階になる。まず共通の`JsonLineFileWriter`では、`JSON_INVALID_UTF8_SUBSTITUTE`等により不正UTF-8を安全化し、可能な限りrecordを残す。directory作成やfile appendを含む保存失敗は、元のwarningを失わない専用例外としてSharedの呼び出し側へ通知し、Shared自身は継続・中断を決めない。次にCMSがアプリケーション固有の`IAuditLogger` wrapperをcontainerで選択し、その専用例外だけを完了済みresponseから分離して、対象writerに依存しないfallback error経路へ通知する。`AuditMiddleware`や共通writerで一律に例外を握りつぶすと、EventBus障害やApplicationLog / SecurityLogの失敗方針まで暗黙に変えるため避ける。application / audit / securityの各loggerへ不正UTF-8を渡すtest、Shared loggerが保存失敗を呼び出し側へ通知するtest、CMS wrapperがその失敗を通知して完成済みresponseを維持するtestを追加する。

READMEはすでに「実運用のAuditLoggerは保存失敗だけで完了済み操作のresponseを壊さない」と正しい方針を書いている。したがって、この問題はREADME不足ではなくsourceが文書上のcontractへ追随していない問題である。ただし、その判断主体はSharedではなくCMSアプリケーションであることを文書でも明確にする。Shared側にはJSON encodingのsubstitute方針と保存失敗を専用例外で返すcontractを、CMS側にはAudit保存失敗時のfallback通知先と継続方針を追記するとよい。ApplicationLog / SecurityLogの保存失敗をrequestへ伝播させるかは別のcontractであり、本所見から一律のbest-effort方針を推論しない。監査配送全体の非同期化やoutbox導入は、この局所的な失敗境界の修正には不要である。

### Shared の検証項目

#### V-SH-01: environment 名だけで CSRF を無効にする運用条件

- 分類: 検証項目（verification item）
- 確信度: unverified（危険な運用条件の成立）
- 場所: `apps/shared/config/container.php:63-75`、`apps/cms/config/container.php:117-134`、`apps/shared/README.md:78-86`
- 確認期限: staging を外部公開する前、および staging が production data / cookie domain を共有する前

`stg`、`staging`、`dev`、`develop`、`development`、`test` では CSRF が自動的に無効になる。これは明示的に文書化された現行方針なので、environment 名だけを根拠に確定 defect とはしない。ただし staging が外部到達可能、実データ接続、または production と cookie domain を共有するなら browser session の防御が消えるため、運用条件の確認が必要である。

確認項目は、各 environment の到達制御、data source、cookie domain、状態変更 route の token なし response。条件を保証できない場合は CSRF を default enabled とし、test composition だけで disabled middleware を注入するか、危険性が明示された opt-out flag にする。単に environment 列挙を増やす修正は避ける。

#### V-SH-02: `PdoRow` の既定値変換が必須列の不整合を隠す可能性

- 分類: 検証項目（verification item）
- 確信度: unverified（現行defectとして）
- 場所: `apps/shared/src/Database/PdoRow.php:22-77`、同helperを利用する41個のpersistence実装
- 確認期限: persistence / schemaの大規模変更前、またはprojection不整合を疑う障害調査時

`int()` は欠落・不正値を `0`、`string()` は空文字、`date()` は既定値へ変換する。`nullableInt()` / `nullableString()` も「非 null だが不正な値」をそれぞれ `0` / 空文字にする。これらは current user、記事、固定ページ、分類、一覧 query、INSERT `RETURNING id` など多数の persistence adapter で、必須列と意図的 fallback の両方に同じ API として使われている。

helperの変換仕様と利用範囲はsource上で確認できた。しかし現行call siteを再点検した範囲では、projection alias漏れや必須列不整合によって実際にID 0や空文字へ誤変換される具体的な経路は確認できなかった。現在のschemaのNOT NULL制約とenum / value objectの検証も一部を保護している。したがって、「不整合を隠し得るAPI」であることと「現行defectがあること」を分け、確定所見やLow件数には数えない。

確認時は、41実装を一括修正するのではなく、必須列を読むcall siteと表示上のfallbackを意図するcall siteを分類する。具体的な不正projectionまたはcontract不一致を確認できた箇所だけを確定所見へ昇格し、必要ならstrict accessorと、呼び出し側が既定値を明示するoptional accessorを分ける。欠落・型不一致でkeyと期待型を含むmapping exceptionが出るtest、null許容はnullだけを受けるtest、表示上のfallbackはcall siteに残るtestが判断材料になる。

### Shared で問題としなかった点

- file cache は現在 non-authoritative cache として使われ、破損値を miss 扱いできる。分散 cache や serializer abstraction への置換は、複数 process の実測競合や共有 backend 要件が出るまでは推奨しない。
- AuditEvent の metadata は controller が任意 blob を直接投げる構造ではなく、`AuditReporter` / middleware の所有境界があるため、型を増やすこと自体を改善には数えない。
- Front と Front Business に似た query / presentation code があることは、標準サイトと業態別サイトの変化点を見極めるための現在の分離として扱い、Shared への先回りした統合を推奨しない。

---

## 修正時の完了条件

各確定所見は独立したcoherent work unitとして、修正と退行testを同じwork unitに含める。test-firstを一律の手順にはせず、失敗を安全に再現できる箇所では先にtestで固定し、native APIや環境依存境界では差し替え可能な境界を整えてから失敗testを追加してよい。Frameworkのgenerated Kernel / Sharedに影響する変更はdrift testと配布packageの同期検査まで通す。

所見ごとの最低限の受け入れ条件は次のとおりである。

- FW-01: `session_start()`、ID再生成、close、destroyの各失敗が成功扱いされず、明示的な例外になる。
- FW-02: method不一致時に405と正しい`Allow` headerを返す。
- FW-03: `kernel` / `shared`のscaffoldがfilesystem、Composer、Kernel configを変更する前に拒否される。
- K-01: process environmentのみ、`.env`のみ、競合、空文字、未設定時既定値の各規則が一つのreaderで固定される。
- K-02: 重複name、重複prefix、non-canonical prefixがroute / middleware fileの読み込み前に拒否される。
- K-03: 標準Kernelでは自動登録され、custom Kernelでは手動案内される二経路を文書とscaffold testが一致して示す。
- SH-01: 不正UTF-8でも有効なJSONLを残し、Sharedは保存失敗の理由を専用例外で通知する。CMSがその例外を独立したfallbackへ通知して、完了済みresponseを維持する方針を明示的に選ぶ。

変更内容に応じて、少なくとも次のsuiteを選んで確認する。

- Framework: package PHPUnit、PHPStan、package boundary / scaffold E2E
- Kernel: root Kernel test、Kernel scaffold drift、`.env` と process env の優先順位 test
- Shared: root Shared test、CMS middleware test、JSONL parse test、対象 persistence integration test
- 全体: root PHPUnit、PHPStan、`composer validate --strict`、`tools/webmedia-skeleton check`

今回の環境では上記commandを実行できていないため、本書は修正完了記録ではない。2026-08-25時点のsourceレビューを、2026-08-26に二次レビューとsource再確認に基づいて再トリアージした結果である。
