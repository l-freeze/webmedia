# 設計判断記録

この文書は「なぜそうするのか」「なぜ今はやらないのか」を残す場所である。

実装手順、過去レビュー、会話ログ、将来構想だけの内容は残さない。将来の誤実装を防ぐために必要な判断だけを短く残す。

## View コンパイルキャッシュ

### 判断

View コンパイルキャッシュは標準では有効にしない。

classic / non-worker runtime で必要になった場合だけ、app container から `LatteTemplateRenderer` へ app 別の `cacheDirectory` を明示して有効化する。

### 理由

- 標準 runtime は FrankenPHP worker mode である。
- worker mode では、一度ロードされた template class が同一 PHP process 内に残るため、定常リクエストでディスク上の compiled view cache の効果は小さい。
- View コンパイルキャッシュは HTML response cache ではない。DB row、記事本文、設定、ログイン状態、CSRF token、権限判定結果はキャッシュ対象にしない。
- template 変更の反映は worker restart の問題として扱う。

### 採用しないこと

- `VIEW_CACHE=true` のような env switch は作らない。
- `autoRefresh=false` 相当の設定は標準では採用しない。
- container / config コンパイルキャッシュは現時点では採用しない。

### 有効化する場合

- cache directory は app 別に分ける。
  - `storage/cache/views/front`
  - `storage/cache/views/cms`
- source 変更検知は有効のままにする。
- HTML response cache、DB query cache、CDN cache とは別機能として扱う。

## Layout Global Provider の cache

### 判断

Front の `LayoutGlobalProvider` は、現時点では provider interface や cache decorator へ分けない。

### 理由

- layout global data は Front の Presentation 層が template 用に合成する表示都合の data である。
- Shared に layout cache service を置くと、Front 固有の責務が横断層へ漏れる。
- 現状は `globals()` と `loadGlobals()` を分けるだけで、cache 制御と data 組み立てを読み分けられる。

### 分離を検討する条件

- CMS 更新時の明示 invalidation が必要になる。
- cache key や TTL が複数になる。
- locale、theme、preview、device、user state で layout data が変わる。
- 同じ TTL cache pattern が他の provider / Query に増える。
- test が cache 制御と data 組み立てで読みにくくなる。

## Middleware 境界

### 判断

Middleware は HTTP request / response の入口制御と横断処理に限定する。

対象リソース依存の業務判断は UseCase / Policy / Domain へ寄せる。

### Middleware で扱ってよいこと

- ログイン済みか確認する。
- route metadata から自身の FQCN を key とする Permission 設定を確認する。
- CSRF token を検証する。
- IP address、user-agent、request id など HTTP metadata を event に載せる。
- CMS の状態変更 request の HTTP envelope を Audit として記録する。

### Middleware で扱わないこと

- route variables の `{id}` から記事を取得して所有者判定する。
- 記事 status を見て公開、復元、削除の可否を判定する。
- Repository / Query を呼び、resource-level policy を実装する。

### 理由

Middleware に対象リソース依存の判定を置くと、同じ UseCase を CLI、batch、将来の API から呼んだときに認可が抜ける。`writer は自分の記事だけ編集可` のようなルールは、route-level permission ではなく UseCase / Policy で判定する。

## Route metadata

### 判断

- Framework の Router は route metadata を解釈せず、`array<string, mixed>` のまま Request へ渡す。
- アプリケーション側の middleware 設定は、その設定を消費する middleware の FQCN を top-level key にする。値の型と意味は各 middleware が所有する。
- CMS routes は login の GET / POST と logout だけを認証用の例外とし、それ以外は表示用 GET を含めて route-level Permission を必須にする。
- 現時点では `RouteMetadataReader` や `MatchedRoute` value は作らない。

### 理由

- route metadata を読む箇所がまだ少ない。
- FQCNをkeyにすれば、文字列keyの衝突を避けながら設定の所有者をroute定義上で明示できる。
- 先に抽象化すると、何のための概念かが半端に見える。
- 現行の route match 2回は理解しやすく、route 数も少ないため性能問題になっていない。

### 導入を検討する条件

- route metadata を読む middleware が増える。
- `Router::ROUTE_METADATA_ATTRIBUTE` 参照が複数箇所へ広がる。
- route 数が増え、match コストが実測で問題になる。
- route name、vars、metadata、controller をまとめた `MatchedRoute` が必要になる。

## IClock と記事公開日時

### 判断

- 現在時刻を業務判断に使う場合は、Shared Application の `IClock` に依存する。実装はShared Infrastructureの`SystemClock`とし、UTCの現在時刻を返す。
- CMS Application は `new DateTimeImmutable()` で現在時刻を直接生成しない。この境界は`tests/Cms/Application/ClockBoundaryTest.php`で検査する。
- 記事公開判定ではSQLの`NOW()`を使わず、IClockから得たUTC時刻をquery parameterとしてbindする。通常Front内の同一公開条件は`ArticlePublicationCondition`に集約する。
- CMSの記事公開日時入力はJSTの`Y-m-d H:i:s`として受け、ApplicationでUTCのtimezoneなし文字列へ変換して保存する。CMSとFrontの表示時にPresentationでJSTへ戻す。
- `front-business`はPOCのため、今回のIClock・公開日時整理の対象外とする。

### 理由

- ログイン試行期限、解除時刻、予約公開、公開終了は、現在時刻が業務判断の入力になる。
- PHP実行環境やDB sessionのtimezoneへ暗黙に依存すると、CMSがJSTとして入力した公開日時とSQLの現在時刻が9時間ずれる。
- IClockを固定できれば期限と公開境界を再現でき、DB serverの時計を変更せずにテストできる。
- UTC保存とPresentationでのJST変換を分けることで、保存値の意味と日本向け表示の責務を明確にできる。

### 採用しないこと

- PostgreSQL session全体をJSTへ変更して差を隠さない。
- FrameworkにIClockやプロジェクト固有timezoneを置かない。
- 既存timestampを一律9時間変換しない。既存値がUTC意図かJST意図かは値だけでは判別できないため、必要な環境では個別に確認する。

## Interface naming

### 判断

- プロジェクトが定義するInterfaceは、Framework、Shared、各appを通して`I` prefixへ統一する。
- ファイル名はInterfaceの短名と一致させる。
- PSRなど外部packageのInterfaceは提供元の名前を維持する。

### 理由

- QueryとRepositoryの大多数が既に`IQuery` / `IRepository`形式であり、`Clock`、`SecurityLoggerInterface`、`TemplateRenderer`などに別規則を残す実益がない。
- `I` prefix、`*Interface` suffix、接辞なしを混在させるより、コード検索とDI定義でInterfaceを一目で識別できることを優先する。
- `I` prefix自体が一般に唯一正しいわけではないが、このproject内では一貫性の利益が上回る。

## Domainの有限集合

### 判断

- CMS Domainで取り得る値が閉じている状態・形式・roleは、文字列定数を持つclassではなくstring backed enumで表現する。
- `ArticleStatus`、`PageStatus`、`ArticleBodyFormat`、`PageBodyFormat`、`CmsRole`、`InquiryStatus`をenumとして扱う。
- HTTP入力では`tryFrom()`でvalidationし、DB復元では`from()`を使って未知の保存値を不整合として検出する。
- Domain内部では文字列比較ではなくenum caseを比較し、DB、session、templateなど文字列境界でだけ`->value`へ変換する。

### 理由

- 許可値の配列、定数、`all()`、validationを別々に保守せず、caseを唯一の定義にできる。
- 未知値を`draft`や`markdown`へ黙って置き換えると入力不正と保存データ不整合を区別できない。
- PolicyやEntityが`string`ではなく有限集合を受け取ることで、不正な状態をDomain内部へ持ち込まない。

### 採用しないこと

- HTTP method、MIME type、環境名など、外部仕様や拡張可能な入力を一律にenum化しない。
- 記事と固定ページの状態・本文形式は、現時点では共通enumへ統合しない。caseが同じでも別コンテキストとして独立させ、共通化が必要になる振る舞いが生じてから判断する。

## Application Input Validation

### 判断

UseCase では、HTTP request body の配列 shape を `Interactor` / Domain / Repository へ持ち込まない。

`apps/front`、`apps/front-business`、`apps/cms` の UseCase 固有 `Input` は public readonly property を持つ DTO とする。

UseCase Inputは、public constructorをControllerから `new Input(...)` する方式と、private constructor + `Input::create()` のfactory method方式のどちらも使ってよい。事前処理が不要なInputで `create()` を使ってはいけないわけではなく、UseCaseの生成APIを明示する目的や、同じアプリケーション内の一貫性を優先してfactory methodを選んでよい。

trim、空値補正、下限補正など、UseCase入力として保持する前の処理がある場合は `Input::create()` に閉じる。factory methodを使う場合もconstructorは値の保持だけを行う。

HTTP body 由来の値は、trim してよい項目だけを項目単位で明示する。本文や password、upload file 配列など、入力そのものを保持すべき項目は一律 trim の対象にしない。

入力検証の結果は Shared の `ValidationOutput` に寄せる。Article / Page のように入力項目が多く、required / slug / rich_text 形式確認などが膨らむ場合は `Application/<Feature>/<Action>/InputValidator` を置く。`Interactor` は `ValidationOutput` を確認し、valid な場合だけ Policy / Repository / Entity 操作へ進む。

### 理由

- Controller に validation を置くと、HTTP 以外から同じ UseCase を呼ぶ入口で検証が抜ける。
- 事前処理の有無だけで生成方式を強制すると、アプリケーション内でInputの呼び出し方が不必要に混在する。
- factory methodは事前処理のためだけでなく、UseCaseの生成入口を明示するAPIとしても使える。
- 事前処理がある場合だけ処理場所を `create()` に限定すれば、constructorを値の保持に留められる。
- Laravel の `FormRequest` 的な HTTP lifecycle hook は分かりやすい反面、UseCase 直前の境界が見えにくくなる。
- Interactor は UseCase の手順を読む場所であり、HTTP body 配列の key 解釈や scalar fallback を抱える場所ではない。
- `Form` は HTTP / 画面入力の語彙なので Application の概念名にはしない。Application では UseCase の入口入力を検証する `InputValidator` として扱う。
- Domain Entity が request body shape を知ると、Domain と Presentation / HTTP 入力の境界が壊れる。
- Repository が request body shape を知ると、永続化境界に入力都合が漏れる。
- PHP で `Result<ValidatedInput, ValidationOutput>` のような型を再現すると、中間型が増えて責務が曖昧になる。このプロジェクトでは、validation 済みであることは `Interactor` の制御フローで保証する。

### 採用しないこと

- Entity に `fromInput(array)` や `stringValue()` / `intValue()` のような入力 helper を置かない。
- UseCase `Input` に `array $data` を持たせない。
- UseCase `Input` の constructor 内で値を加工しない。
- factory methodを使う理由を作るために、不要な事前処理や抽象化を追加しない。
- request body 全体を一律 trim する middleware は置かない。
- `ValidatedInput` や `ValidationResult` のような PHP 版 Result 型を作らない。
- Controller / middleware / lifecycle hook で UseCase 固有 validation を完結させない。
- `InputValidator` を複数 UseCase で共有する汎用 DTO にしない。必要な UseCase 配下に置く。

## Query / Repository の戻り値

### 判断

Queryの戻り値はApplication Value、Repositoryの戻り値はDomain Entity / Domain Valueを基本とする。

primitiveを直接返してよいのは、件数を表す `int` と、存在・許可・重複・該当有無・操作成否などの真偽判定を表す `bool` に限る。これはprimitiveを許容する規則であり、件数や真偽値をValueで表現することを禁止するものではない。

それ以外のID、金額、状態、日時、識別子、複数項目を持つ結果は、QueryではApplication Value、RepositoryではDomain EntityまたはDomain Valueで表現する。戻り値を必要としない書き込み操作は `void` でよい。

### 理由

- 件数だけを持つValueや真偽値だけを持つValueは、型を増やしても意味や不変条件を追加しない場合がある。
- `count...(): int`、`exists...(): bool`、`has...(): bool` のようにmethod名と型だけで意味を限定できる場合は、primitiveのままでも境界が曖昧にならない。
- scalar を全面許可すると、ID、状態、DB row、複数項目の結果まで primitive に逃がせるため、例外を件数と真偽判定に限定する。

### 採用しないこと

- 全scalar戻り値を一律にValueで包むことを要求しない。
- 件数や真偽値に独自の意味や不変条件がある場合まで、primitiveを強制しない。
- 生成 ID を `int` のまま返すために count の例外を流用しない。
- 複数項目を連想配列で返すために bool / int の例外を拡張しない。

## Audit / SecurityLog

### 判断

Audit、SecurityLog、HTTP access log は分ける。

CMS の状態変更 HTTP request は `AuditMiddleware` で記録する。状態変更が成功した場合は Controller が `AuditReporter` で業務要約を request の audit trail へ注入する。状態変更が発生していない場合は `AuditReporter` を呼ばない。最終的な `AuditEvent` dispatch は middleware が 1 回だけ行う。

`AuditMiddleware` は、業務要約がある request を Audit として記録する。業務要約がない正常応答は、validation failure や表示遷移など状態変更なしの request として扱い、Audit には残さない。業務要約がない 4xx / 5xx / 例外は、状態変更前または途中の失敗として汎用 failure を記録する。

監査対象の状態変更 route は、成功時に `AuditReporter` を呼べるよう Controller が `AuditReporter` を constructor injection する。例外は login / logout のように SecurityLog で扱う route だけとし、architecture test の除外リストに明示する。

BusinessAudit という別ログ種別は作らず、既存の `AuditEvent` に集約する。区別は `action`、`resource_type`、`metadata` で表現する。

監査ログは「操作名だけ」では不足する。必要なのは汎用的な before / after blob ではなく、後から説明・調査できる情報群である。重要な更新系では対象 ID、結果、変更された項目、主要項目の値変化、本文の hash / 文字数などを残す。ただし本文全文、問い合わせ本文、password、CSRF token、cookie、session id、過度な個人情報は残さない。

### 理由

- HTTP request と業務操作は一対一とは限らないため、HTTP envelope だけでは説明不足になる操作がある。
- UseCase が AuditSummary を返すと、業務処理の返り値に監査ログ都合が混ざる。
- Controller が AuditEvent を直接組み立てると、Controller 追加や Audit 仕様変更のたびに横断的な修正が必要になる。
- middleware は横断処理の入口として妥当だが、DB schema や業務対象の差分計算を知るべきではない。
- Controller は HTTP 操作の成功後に、必要な業務要約だけを `AuditReporter` へ渡す。actor、IP、user-agent、request-id、最終ログ出力は middleware に残す。
- PRG では成功時も validation failure 時も redirect になるため、HTTP status や Location だけでは業務成功/失敗を判定しない。業務要約の有無を「状態変更成功」の明示 signal として扱う。
- `AuditReporter` の呼び忘れは実行時には検出しにくいため、状態変更 route の Controller が `AuditReporter` を受け取ることを architecture test で検査する。
- 記事本文や固定ページ本文の過去内容は履歴機能の責務であり、Audit は「誰が、いつ、どの対象に、どの種類の変更をしたか」を説明できる最小要約に留める。
- HTTP envelope と業務イベントはどちらも監査証跡であり、保存先や閲覧 UI を分けるほどの運用要件は現時点ではない。
- OWASP Logging Cheat Sheet は、ログの目的に合わせて十分な情報を残し、when / where / who / what、action、object、result などを記録する考え方を示している。一方で sensitive personal data や secrets は除外・mask・sanitize するべきとしている。
- NIST SP 800-92 は、ログ管理の policy / process / infrastructure を整えることを重視している。つまり粒度は実装都合ではなく、後から何を検証したいかで決める。

### 採用しないこと

- 全 GET request を Audit に保存しない。
- CMS login 成功 / 失敗を Audit として保存しない。SecurityLog として扱う。
- request body 全体、password、CSRF token、cookie、session id を保存しない。
- middleware で認可や業務成立条件を判定しない。
- middleware からDBを読んで before / after 相当を作らない。
- 全テーブル汎用の差分記録エンジンを作らない。対象 route と記録項目は CMS の監査目的に合わせて明示する。
- 汎用的な before / after blob を Audit UI の中心概念にしない。
- `NullAuditLogger` を標準にしない。未設定を黙って捨てると検知できない。
- `BusinessAuditLogger` のような別 logger を作らない。必要になるまでは `AuditEvent` に集約する。
- 変更内容を知るために request body 全体を保存しない。対象 UseCase ごとに必要な項目だけを要約する。
- validation failure のように状態変更がない正常応答を Audit success として残さない。
- 状態変更成功の判定を redirect 先の path pattern に依存させない。

### 記録する粒度

- 記事更新: `title`、`slug`、`status`、`category_id`、公開日時、SEO 項目などの変更項目。本文は全文ではなく hash、文字数、変更有無に留める。
- 記事作成 / 削除 / 復元: 対象 ID、title、slug、status、復元元 version。
- 固定ページ作成 / 更新 / 復元: 対象 ID、title、slug、status、display_order、SEO 項目、本文 hash / 文字数、復元元 version。
- 設定更新: 変更された setting key と値変化。秘密値が出る設定が増えたら mask 対象にする。
- 管理者作成 / 更新 / 削除: 対象 ID、email、name、role。password / password_hash は残さない。
- 問い合わせ更新: status、管理メモの有無・文字数・hash。問い合わせ本文は残さない。
- カテゴリ / タグ更新: name、slug、SEO 項目など運用上説明に必要な値。
- メディア削除: 対象 ID、file_name、path、mime_type。
- メディアアップロード: 対象 ID、元ファイル名、mime_type、file_size、alt / caption の有無。
- 問い合わせ閲覧や export を追加する場合: 個人情報アクセスとして Audit 対象にする。問い合わせ本文は Audit に複製しない。

### Pub/Sub 方式の将来方針

現時点では `AuditReporter` を Controller から明示的に呼ぶ方式を採用する。将来、Controller 追加ごとの `AuditReporter` 追記が負担になった場合は、ApplicationEvent / BusinessEvent を publish し、Audit subscriber が `AuditSummary` へ変換する方式を検討する。

この event は DomainEvent とは呼ばない。DomainEvent は、Domain の状態遷移として意味がある出来事である。Fowler の Domain Event は、domain に影響する出来事を記録し、その event stream を audit log にも使えるという考え方を示している。一方、この CMS の監査要約は HTTP actor、request id、IP、user-agent、画面操作上の action 名、mask 済み metadata など Presentation / Application の文脈を含む。Domain 層が `AuditSummary` や HTTP 情報を知るべきではない。

Pub/Sub 方式を採る場合の候補は次の通り。

- Controller が UseCase 成功後に ApplicationEvent を publish する。
  - 利点: 現行の `AuditReporter` と同じ場所で置き換えやすい。
  - 欠点: Controller が「どの成功が状態変更か」を知り続ける。HTTP 以外から同じ UseCase を呼ぶ場合に event が出ない。呼び忘れ問題も残る。
- UseCase が状態変更成功後に ApplicationEvent を publish する。
  - 利点: 状態変更が成立した場所に近く、HTTP / CLI / batch など入口が増えても同じ event が出る。後から監査以外の subscriber を追加しやすい。
  - 欠点: UseCase が EventBus に依存する。同期実行にするか、audit 書き込み失敗を業務失敗にするか、DB 更新と event dispatch の一貫性をどう保つかを設計する必要がある。ただし、同期 EventBus で同一 request 中に Audit を保存する限り、outbox は不要である。
- Entity / Repository lifecycle hook で拾う。
  - 利点: 保存処理に近く、作成/更新/削除の検出は漏れにくい。Laravel Eloquent の model events / observers はこの系統である。
  - 欠点: どの UseCase で何の意図で変わったかが見えにくい。Observer / signal は便利だが追跡が難しくなりやすい。Django docs も custom signal について、同一 project 内なら直接呼び出しの方が理解・変更・debug しやすいと警告している。
- UseCase Output に `AuditSummary` を持たせる。
  - 利点: Controller は Output を middleware や reporter に渡すだけにできる。
  - 欠点: UseCase の返り値に監査表現が混ざる。UseCase は Audit があってもなくても成立するため、現方針では採用しない。

決め兼ねている点:

- Pub/Sub を採る場合、event dispatch は同期にするか非同期にするか。
- Audit subscriber の失敗を UseCase の失敗として扱うか、業務成功を優先して別途 error log にするか。
- DB 更新と Audit/Event 保存の一貫性をどこまで保証するか。同期 EventBus で同一 request 中に Audit を保存する限り、outbox pattern は導入しない。
- ApplicationEvent の粒度を `ArticleCreated` のような個別 class にするか、`CmsStateChanged` のような汎用 event + action code にするか。
- HTTP request metadata を UseCase publish event に含めるか、request scope の context provider から subscriber が取得するか。

outbox pattern を再検討する条件:

- event dispatch / Audit 保存を非同期 worker に移す。
- Audit subscriber の失敗で業務処理を rollback したくないが、event は失いたくない。
- retry 可能な event 配信保証が必要になる。
- message broker や外部 service へ event を配信する。
- Audit 以外の subscriber が増え、request 中の同期処理に置き続けるのが重くなる。

当面の結論:

- DomainEvent ではなく ApplicationEvent / BusinessEvent として検討する。
- UseCase が直接 `AuditSummary` を返す方式は採用しない。
- lifecycle hook / observer は、暗黙性が強く、このコードベースでは採用しない。
- 現時点では `AuditReporter` を厚くし、architecture test で state-changing route の reporter 漏れを防ぐ。
- Pub/Sub 化する場合も、まずは同期 EventBus を前提にし、outbox は再検討条件が発生するまで採用しない。

参考:

- OWASP Logging Cheat Sheet: https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html
- Martin Fowler, Domain Event: https://martinfowler.com/eaaDev/DomainEvent.html
- Django Signals: https://docs.djangoproject.com/en/5.2/topics/signals/
- Laravel Eloquent Events / Observers: https://laravel.com/docs/12.x/eloquent#events

## 公開前仕様

### 判断

`robots.txt`、`sitemap.xml`、OGP / Twitter Card、RSS / Atom は場当たり的に実装しない。公開前必須に近いが、先に仕様を決めてから着手する。

問い合わせフォームは当面積極運用しない。ただし外部入力口であるため、運用を開始しない場合でも公開前には最低限の rate limit / spam 対策 / 個人情報取得説明を判断する。直近優先の実装課題ではなく、忘れてはいけない公開前保留項目として扱う。

### 理由

- SEO / SNS / 配信系は一度公開すると外部 crawler や reader に観測されるため、後から雑に変えると挙動が読みにくい。
- 問い合わせフォームは小規模でも spam と個人情報取得の入口になる。

### 採用しないこと

- 仕様未確定のまま OGP / sitemap / feed を仮実装しない。
- 問い合わせフォームを運用しないことを理由に、防御を完全に省略しない。

## 記事本文エディター

### 判断

CMS の記事本文は Markdown、HTML、リッチエディターから選べるようにする。Markdown は捨てない。HTML は既存互換と直接HTMLを書きたい場合のために残す。

リッチエディターは Tiptap の OSS editor を採用する。ただし利用範囲は自前ホストの editor package に限定し、Tiptap Cloud、AI Toolkit、Collaboration、Pro Extensions、有料 Notion-like template は採用しない。Notion-like な `/` コマンドメニューは Tiptap core / StarterKit のデフォルト機能ではなく、公式 docs でも Slash Commands は実験的で未公開 package 扱いのため、この CMS に必要な最小機能だけを自前 UI として実装する。文字列選択時の装飾メニューも CMS 側の自前 UI とし、マウスでの範囲選択中はドラッグを妨げないようにメニューを出さず、mouseup 後に表示する。画像挿入は URL 手入力に寄せず、CMS 登録済み画像を選べる UI を優先する。

保存形式は、選択中の `body_format` に対応する本文ソースを `body` カラムに保存する。`markdown` は Markdown 文字列、`html` は HTML 文字列、`rich_text` は Tiptap / ProseMirror JSON 文字列を保存する。`rendered_body_html` は本文ソースから生成した表示用 HTML の派生 cache として保存し、正本にはしない。記事は検索・抜粋用の派生 plain text として `search_body_text` も保存する。公開表示では `rendered_body_html` を優先し、未生成の既存行では PHP 側で許可 node / mark だけ HTML 化し、既存の HTMLPurifier を通してから出力する。

CMS の形式切替は保存済みの正本を即時変換する処理ではなく、編集画面内で形式別ドラフトを保持する UI とする。リッチエディターから Markdown / HTML へ切り替えた時は textarea を JSON で上書きしない。選択先のドラフトが空の場合だけ、リッチ本文から Markdown 風テキストまたは HTML へフォールバック変換して表示する。

### 理由

- Markdown 記事資産と運用感を維持できる。
- Tiptap は ProseMirror を直接扱うより UI 実装が薄く済み、Notion-like な編集体験へ寄せやすい。
- `# ` などの Markdown shortcut は Tiptap Heading input rule を使う。`/` メニューは候補表示と node 変換だけを CMS 側で薄く実装する。
- 文字列選択時の装飾メニューは本文編集の中心操作なので、リンククリックでは画面遷移せず、編集用のリンク設定/解除 UI を出す方が自然である。
- Tiptap / ProseMirror 本体は編集状態と command を扱うライブラリであり、CMS の装飾メニュー用アイコンはアプリ側の UI として管理する方が依存を増やさずに済む。
- Tiptap GmbH はドイツ Berlin の法人として Legal Notice を公開しており、OSS editor は MIT license で公開されている。外部 SaaS に本文を預けず、自前ビルド・自前DB保存に限定すれば、運用上のチャイナリスクや vendor SaaS 依存を抑えられる。
- JSON を正本にすると、段落、見出し、リスト、画像、引用、表、区切り線などを構造として扱える。
- 画像挿入はメディア管理の登録済み画像を選ぶ方が、利用者に `/uploads/...` のパスを覚えさせずに済む。
- 表は記事本文の構造として自然に必要になる可能性があり、Tiptap の OSS table extension と Shared renderer の許可 node として扱えば本文モデルを増やさずに済む。
- 表示用 HTML をクライアントから信頼せず、サーバ側 renderer と sanitizer を通す方が XSS 境界が明確になる。
- Tiptap 公式 API も `getJSON()` と `getHTML()` の両方を公開しているため、JSON を編集用の構造化 source、HTML を表示用の派生結果として扱う設計は自然である。
- Front が欲しいのは rich_text 限定 HTML ではなく、本文形式に関係なく表示できる安全な HTML である。そのため列名は `rich_text_html` ではなく `rendered_body_html` とする。
- `content` は記事全体や content type まで広く読めるため、記事本文の正本には英語圏の CMS / blog 文脈で自然な `body` を使う。
- 表示用 HTML を派生 cache として保存すると Front は表示時の JSON parsing / HTML 変換を避けられる。一方で renderer 変更時は再生成が必要になるため、正本はあくまで `body` と `body_format` とする。
- rich_text の正本は JSON であり、Front の検索・抜粋が本文 source を直接 `strip_tags` する設計は形式依存になる。検索と未入力時抜粋には、CMS 保存時に生成した `search_body_text` を使う。
- 固定ページも本文編集体験と表示境界は記事と同じなので、`body` / `body_format` / `rendered_body_html` に揃える。ただし現時点の Front 検索対象は記事だけなので、固定ページには `search_body_text` を持たせない。
- OGP / Twitter Card / RSS / sitemap は公開表現であり、CMS の保存責務ではない。CMS が持つのは `seo_title`、`meta_description`、`excerpt` など入力値までとし、公開用 meta や XML/feed への整形は Front Presentation 側で行う。

### 採用しないこと

- 本文形式を EAV や汎用フィールド定義エンジンにしない。
- Tiptap Cloud / AI / Collaboration へ接続しない。
- CDN から Tiptap を読み込まない。
- 公式 Slash Commands 実験 extension をそのまま vendor copy しない。
- クライアント生成 HTML だけを正として保存しない。
- `rendered_body_html` を編集用 source として扱わない。
- リッチ本文のために公開 front を Node.js rendering へ依存させない。
- 編集用 JSON と表示用 HTML の二重正本を持たない。

### 拡張を検討する条件

- 画像の本文挿入時に、その場でアップロードできる必要が出る。
- Markdown から rich_text への明示変換が必要になる。
- renderer の許可 node / mark を変更した場合、既存 `rendered_body_html` の再生成 command を用意する。
- renderer の本文テキスト抽出仕様を変更した場合、既存 `search_body_text` の再生成 command を用意する。
- 記事履歴差分で JSON の node 単位比較が必要になる。

## メディア派生情報

### 判断

メディアファイルは、元ファイルの `path` / `mime_type` / `file_size` に加えて、表示時に必要な派生メタとして `width` / `height` / `thumbnail_path` / `optimized_path` を `media_files` に保存する。

`width` / `height` は画像アップロード時に取得して保存し、既存画像は migration で backfill する。動画寸法は ffprobe など別の実行環境依存が必要になるため、現時点では取得しない。

`thumbnail_path` と `optimized_path` は生成済みファイルへの path として扱う。生成処理は PHP で画像処理を実装せず、Infrastructure 側から libvips CLI の `vipsthumbnail` を shell 経由ではなく配列コマンドで呼び出す。JPEG / PNG / WebP はアップロード後に同期で WebP のサムネイルと最適化画像を生成する。GIF はアニメーション喪失を避けるため現時点では派生生成しない。

`optimized_path` は現時点の出力が WebP でも `webp_path` にはしない。将来 AVIF などへ変更できる余地を列名に残すためである。

使用先は今すぐ汎用 EAV や参照テーブルにしない。CMS メディア一覧では、記事アイキャッチ、記事本文、固定ページ本文、サイト設定から表示用に導出する。本文内画像の正確な参照管理や削除防止が必要になった時点で、本文保存時に使用先を再計算する専用テーブルを検討する。

### 理由

- Front / CMS は画像表示時にファイルを毎回読まず、DB の寸法や派生 path を見ればよい。
- 寸法は `<img width height>`、画像カード、OGP、RSS、lazy loading、CLS 対策に使える基礎情報である。
- サムネイルや最適化画像の生成前に保存先を決めておくと、後続の変換処理を場当たり的にしなくて済む。
- libvips はコンテナに CLI として入れれば PHP runtime への画像処理責務を増やさずに済む。CMS アップロード時の同期処理であり、失敗しても元画像の登録は成立させられる。
- 使用先は本文 JSON / HTML、アイキャッチ、設定など複数の文脈にまたがるため、最初から汎用参照管理へ寄せると複雑さが先行する。

### 採用しないこと

- 画像表示のたびに `getimagesize()` を呼ばない。
- `webp_path` のような形式固定の列名にはしない。
- PHP ライブラリで画像変換処理を抱え込まない。
- 現時点では動画寸法を推測しない。
- 使用先管理を EAV や全テーブル汎用参照エンジンにしない。

## Application Port

### 判断

Port は最初の選択肢ではなく、Application が外側の能力を本当に必要とし、他の配置では依存方向や責務が崩れる場合に使う。

詳細は `docs/application_ports.md` を正とする。

### 例

- password verification は Application が必要とする外側の暗号処理能力なので Port 化してよい。
- Logger は PSR-3 を shared / framework の横断基盤として使えば足りる間は、Application Port 化しない。
- Audit も通常は shared / framework / presentation 側の横断基盤でよい。業務成立条件として証跡記録が必要になった場合だけ、業務名を持つ Port を検討する。

## Commit 粒度

### 判断

実装作業の commit は、意味のある作業単位に分ける。特に変更対象の境界が異なる場合は、同じリファクタリングテーマでも commit を分ける。

基本の分け方:

- Framework の変更
- Kernel の変更
- Shared の変更
- `apps/cms` の変更
- `apps/front` の変更
- docs / tests の追随

docs / tests は、対応する実装 commit に含める方が追いやすい場合は同じ commit に入れてよい。複数境界にまたがる純粋な機械的変更は、境界ごとに commit を分ける。

### 理由

- 依存境界ごとに review しやすくする。
- revert するときに Framework / Kernel / Shared / app の影響範囲を分離できるようにする。
- 「同じ目的」でも責務の違う層をまとめると、後から変更理由を追いにくくなる。

### 採用しないこと

- 1行ごとの過度に細かい commit。
- Framework、Kernel、Shared、app の変更を、単に同じリファクタリング名だけで一つにまとめること。

## Session lifecycle

### 判断

KernelはRequestごとに一つの `Session` を生成してContainerへ登録する。ただしnative PHP Sessionは、middlewareまたはControllerが `Session` stateへ最初にアクセスした時点でFramework内部から開始する。KernelはRequest終了時に `finally` でcloseし、未開始の場合は何もしない。

`Session::get()`、`set()`、flash、CSRF token、認証など、既存の利用APIは変更しない。開始判断は `Session` 内部の単一state gatewayへ閉じ、個々のControllerやmiddlewareには持たせない。

### 理由

- 記事、カテゴリ、タグ、固定ページ、robots、sitemapなど、Sessionを使わない公開requestへSession Cookieとsession cache headerを付けない。
- 問い合わせのCSRFとCMS認証は、既存の `Csrf` / `Session` 依存を通じて必要時に開始できる。
- Session利用の事実と別に設定を持たないため、設定漏れと実装の不一致が起きない。
- Sessionの生成はrequest scope、開始はFramework、closeはKernelというlifecycle境界を維持できる。

### 採用しないこと

- `ApplicationDefinition`へSession有効フラグを追加しない。
- route metadataへ `requires_session` を追加しない。
- Session対象pathの一覧をKernelまたはmiddlewareへ持たせない。
- Sessionを避ける目的でFrontを別applicationへ分割したり、問い合わせのCSRF方式を変更したりしない。

## Framework packageとKernel scaffold

### 判断

Frameworkのpublic namespaceは `Pera\Framework` とする。`App` namespaceは利用プロジェクト側のKernel、Shared、各appだけに使う。

FrameworkはComposer libraryとして配布する。Framework libraryのinstallはKernelの生成を暗黙に実行しない。Kernelを必要とする利用者だけがscaffoldを明示的に実行する。新規project向けには、Frameworkをrequireしたproject skeletonを別の配布単位として用意する。

monorepo内でもFrameworkは`packages/framework`を自己完結したpackage rootとし、production code、独立manifest、明示scaffold binaryを同じ配布境界へ置く。webmedia rootはComposer path repositoryから`pera/framework`をrequireし、root PSR-4でFramework namespaceを直接autoloadしない。

scaffoldで生成された `App\Kernel` は、生成直後から全て利用プロジェクトの所有物である。FrameworkはKernelへ依存せず、Framework updateも生成済みKernelを上書き・自動upgradeしない。

Framework packageの依存はFramework production codeが直接使うものに限定する。Kernel / Shared / appの生成物が使うDotenv、Latte、DB、migration、content、mail、mediaの依存はproject skeletonまたは利用側root packageが所有する。

標準Kernel scaffoldは`.env`が存在する場合にDotenvで任意ロードする起動処理を持つ。環境変数の読み込みはcontainer構築や各appの起動より前に一度行うapplication compositionの責務なので、Sharedには置かない。`vlucas/phpdotenv`は生成Kernelを使うconsumer rootまたはproject skeletonの依存であり、Framework runtime packageの依存にはしない。

正式なscaffold support対象はKernel、Shared、Appの`bare` / `minimal` / `todo` / `layered`全種とする。特定templateだけをpackage導入経路から外さず、packaged artifactからのconsumer E2Eで各生成契約を検証する。予約名の`fullpack`は実装されるまでsupport対象ではない。

scaffoldが外部packageを直接参照するsourceを生成する場合は、生成機能とComposer依存を同じレシピで対応付ける。現在はShared scaffoldとAppの`minimal` / `todo` templateが、consumer rootのruntime `require`へ`latte/latte:^3.0`を追加する。Kernel、`bare`、`layered`はLatteを生成しないため追加しない。既存のruntime制約は利用者の判断として維持し、`require-dev`にだけある制約はruntimeへ移す。

scaffoldはroot `composer.json`だけを更新し、依存解決、download、`composer.lock`更新を内側から実行しない。更新が必要な場合は実行者へ`composer update`を表示し、既存lockがあれば追加packageだけへ更新範囲を限定する。manifestが無いrootへの生成は許可して`composer require`を案内し、不正なmanifestでは生成前に失敗する。

PSRの採用範囲は既存のFramework各READMEとtechnical stackを正とする。package化だけを理由にPSR-7 / PSR-15等への移行を新しい残作業にはしない。

Framework packageはComposer installer pluginを持たない。install時にKernelやSharedを自動生成せず、将来自動生成が必要になった場合だけ独立plugin packageとして再設計する。

### 理由

- `Pera\Framework` なら、consumer projectの `App` namespaceとFrameworkのpublic APIを区別できる。
- KernelはFrameworkを使うための便利な初期構成であり、Framework成立の必須条件ではない。
- 利用者所有の生成codeをdependency updateが書き換えると、利用者の変更とのownership conflictが起きる。
- scaffoldの具象実装を理由にFrameworkへDotenv、Latte、DB関連を持たせると、runtime coreの依存境界がstarter都合で膨らむ。
- 環境読み込みをSharedへ置くと、複数appが利用する共有サービス層がprocess全体の初期化順序まで所有してしまう。Kernelなら起動時の一回だけに限定できる。
- 生成sourceとroot依存を同じ機能レシピで扱えば、Latteを使わない利用者へ不要なpackageを課さず、Latteを生成した利用者のmanifest不足も防げる。
- scaffold内からComposerを再帰実行しなければ、network、lock全体の再解決、plugin/script実行を暗黙の副作用にせず、利用者が明示的に確認できる。
- 明示commandまたはproject skeletonなら、Composer pluginによる暗黙のfilesystem変更と `allow-plugins` 依存を避けられる。

### 採用しないこと

- `App\Framework` をpublic package namespaceとして維持しない。
- `composer require pera/framework` の副作用としてKernel / Sharedを自動生成しない。
- Framework packageが生成済みKernelをupgradeまたは上書きしない。
- Shared scaffoldがLatteを生成することだけを理由にFramework packageへLatteをrequireしない。
- Kernel scaffoldがDotenvを使うことだけを理由にFramework packageへDotenvをrequireしない。
- Sharedから`.env`を読み込まない。
- 現行要件が変わらないままPSR対応方針を再検討しない。
