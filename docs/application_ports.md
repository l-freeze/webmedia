# Application Port 判断基準

## 目的

このドキュメントは、Application から外側の機能を使うときに、Port を切るべきか、具象実装をそのまま外側で使えばよいかを判断するための基準である。

本プロジェクトでは、Port を「Application が外側に要求する能力を表す Interface」として扱う。Port は独立したレイヤー名ではなく、依存方向を保つために Application 側へ置く境界である。

## 基本方針

Application はユースケースの手順と業務判断を表す。Application の中に、DB、ファイルシステム、HTTP API、外部ライブラリ、PHP 組み込みの技術詳細、現在時刻、メール送信、セッションの読み書きなどの外側の都合を直接入れない。

ただし、Application がそれらの機能を必要とすることはある。その場合、Application 側には「何が必要か」だけを Interface として置き、具体的な実装は Infrastructure、Presentation、Framework、Shared など外側の層に置く。

```text
Application Interactor
  -> Application Port interface

Infrastructure / Presentation / Framework / Shared
  -> implements Application Port interface
  -> DB / file / HTTP API / library / PHP built-in
```

Port は、Application に内包してはいけないものを安全に使うための入口である。

ただし、Port は設計を良く見せるための道具ではない。本プロジェクトでは、Port は最初に選ぶ選択肢ではなく、Application が外側の能力を本当に要求していて、他の配置では依存方向や責務が崩れる場合の手段として扱う。

## Port を切ってよい条件

Port を切ってよいのは、次の条件を満たす場合に限る。

- Application のユースケースを実現するために、その能力が必要である。
- その能力の実体が Application の内側に置けない技術詳細、外部I/O、外部サービス、実行環境依存である。
- Application が具象実装を直接知ると、依存方向が外側へ向いてしまう。
- Interface 名が、外側の実装名ではなく Application 側の要求を表している。

たとえば CMS ログインでは、Application が必要としているのは `password_verify()` そのものではなく「入力パスワードが保存済みハッシュと一致するかを検証する能力」である。そのため、Application 側に `IPasswordVerifier` を置き、PHP の `password_verify()` 呼び出しは Infrastructure 側の実装に閉じ込めるのが自然である。

## Port を切るべきではない条件

次の理由だけで Interface を作ってはならない。

- 将来差し替えるかもしれない。
- テストで mock したい。
- 複数実装がありそう。
- DI コンテナに登録しやすい。
- class 名に対して機械的に interface 名を作れる。
- 技術名を抽象化すれば設計が良く見える。
- PSR などの標準 contract を見た目上だけ独自 contract で包める。
- 横断的な framework / shared 基盤を、ユースケース固有の要求がないまま Application Port に見立てられる。

Port が必要に見える場合でも、先に責務分割、UseCase 分割、配置、データ構造を疑う。

たとえば通常の application logger は、現時点では PSR-3 `LoggerInterface` を shared / framework 側で扱えばよい。PSR-3 は具象実装ではなく、ロギング相互運用のための標準 contract である。Application のユースケースが「業務イベントとして何を記録するか」を要求しているなら event value や用途別 logger を検討するが、PSR-3 を薄く包むだけの `IApplicationLogger` は作らない。

## Port と Infrastructure の関係

メール送信、現在時刻、認証中ユーザー、パスワード検証、Markdown 変換、ファイル保存、外部 API 呼び出し、トランザクションは、実装としては Infrastructure、Presentation、Framework、Shared の責任範囲に置かれることが多い。

重要なのは、実装の置き場所ではなく、Application が何へ依存するかである。

```text
悪い例:
Application -> Infrastructure Mailer
Application -> PDO
Application -> CommonMark converter
Application -> password_verify()

良い例:
Application -> MailSender port
Application -> TransactionRunner port
Application -> MarkdownRenderer port
Application -> IPasswordVerifier port
Infrastructure -> implements those ports
```

つまり、Port は Infrastructure の代替レイヤーではない。Infrastructure に置くべき実装を Application から直接見ないための、Application 側の契約である。

## 例

### Audit / Log

通常ログ、HTTP envelope audit、security log は、まず shared / framework / presentation 側の横断基盤として扱う。これらは「ユースケースを成立させるために Application が外部能力を要求している」よりも、「実行基盤が操作や通信の事実を記録している」性質が強い。

そのため、PSR-3 logger や標準的な `IAuditLogger` を薄く包むだけの Application Port は作らない。

一方で、次のように監査記録そのものが業務成立条件になる場合は、Application 側の要求として扱う余地がある。

- 承認、契約、医療記録、投票、決済などで、監査証跡の保存に失敗したら業務操作も成立させてはいけない。
- 証跡を改ざん困難な外部台帳、WORM storage、電子署名付き保管庫などへ保存する必要がある。
- 業務ルール上、操作の before / after、承認者、根拠文書、署名、時刻証明を同一 transaction 境界で扱う必要がある。

この場合でも、最初に `AuditLogger` という汎用 Port を作るのではなく、`RecordContractSigningEvidence`、`RecordMedicalRecordAccessEvidence`、`RecordApprovalEvidence` のように、業務上の証跡名で表現できるかを先に検討する。

### パスワード検証

Application が必要とする言葉:

```text
IPasswordVerifier::verify(plainPassword, passwordHash): bool
```

外側の実装:

```text
NativePasswordVerifier -> password_verify()
```

Application は PHP の関数名、アルゴリズム、再ハッシュ判断、監査ログなどの技術詳細を知らない。

### 現在時刻

Application が公開期間、予約公開、期限切れ判定を行うなら、現在時刻はユースケース判断の材料になる。ただし `new DateTimeImmutable()` を Application 内で直接呼ぶと、実行時刻に依存してテストしづらく、batch や再実行時の基準時刻も差し替えにくい。

この場合は `IClock` のような Port を置く価値がある。

### Markdown 変換

Presentation や Infrastructure が表示用に Markdown を HTML へ変換するだけなら、具象 `Markdown` を外側で直接使ってよい。

一方、UseCase が「保存前に本文を安全な HTML に変換する」「プレビュー HTML を Application Output として返す」などを始めるなら、Application が Markdown ライブラリを直接知ることになる。この場合は次のどちらかを検討する。

- そもそもその変換は Application の責務か見直す。
- Application の責務であるなら `MarkdownRenderer` などの Port を切る。

## 本当に Port が必要になりやすい外部能力

業種ごとに機能を洗い出しても、Port が必要になる理由はおおむね次の外部能力に集約できる。

### 外部の事実確認

Application が業務判断のために外部の現在状態を確認しないといけない場合。

- 本人確認、法人確認、資格確認、反社チェック、政治献金の適格性確認。
- 医療保険資格、診療資格、薬剤相互作用、検査結果、紹介状、外部 EHR / EMR 照会。
- 口座存在確認、残高照会、与信、AML / CFT、制裁リスト、反社チェック、不正検知、支払承認。
- 在庫引当、配送可否、契約ステータス、電子署名ステータス。

候補名は `IdentityVerificationGateway` ではなく、Application の語彙で `VerifyCustomerIdentity`、`CheckPatientEligibility`、`AuthorizePayment` などにする。

### 外部への不可欠な副作用

Application の完了条件に外部副作用が含まれる場合。

- 決済の確定、返金、売上計上、請求書発行、電子契約の送信、署名依頼。
- 振込、口座振替、入出金、残高拘束、ローン実行、利息計上、為替予約、勘定系への記帳。
- 医療機関や行政機関への届出、処方箋送信、検査依頼。
- 基幹システムへの受注連携、会計仕訳連携、在庫引当、配送依頼。
- 政治資金報告、選挙運動関連の届出、公開義務のある記録送信。

外部通知メールのように失敗しても後で再送できるものは、まず event / queue / notification 基盤でよい。業務成立条件なら Port 候補になる。

### 外部時刻と証明

時刻が業務判断や証明に直接関わる場合。

- 契約締結時刻、電子署名時刻、医療記録作成時刻、投票締切、注文締切。
- 銀行営業日、カットオフ時刻、約定時刻、決済日、利息計算基準日、金融商品取引の時刻証明。
- SLA、期限超過、公開予約、予約受付、キャンペーン適用、監査証跡の時刻証明。
- 外部 timestamp authority、NTP、クラウド署名時刻などを使う場合。

単に `new DateTimeImmutable()` をテストで固定したいだけなら安易に広域 `IClock` を作らない。複数ユースケースの業務判断で時刻が中心になるなら `IClock` または用途別の時刻 Port を検討する。

### 暗号、署名、鍵管理

Application が秘密鍵、署名、暗号処理の技術詳細を知るべきでない場合。

- 電子署名、契約書ハッシュ、改ざん検知、医療文書署名、投票データ署名。
- KMS / HSM による鍵管理、トークン発行、パスワード検証、証明書検証。
- 銀行 API の署名、取引メッセージの MAC、カード情報 tokenization、PIN / 認証情報の検証。

`IPasswordVerifier` はこの分類に入る。PHP 組み込み関数を直接呼ぶかどうかより、Application が暗号アルゴリズムや鍵管理を知るべきでない点が判断理由である。

### 永続化先が業務上の外部境界である保存

Repository で扱う通常 DB 永続化とは別に、保存先そのものが業務上の外部境界になる場合。

- 電子契約原本、医療画像、診療記録、証憑、請求書、監査証跡の WORM storage。
- 取引明細、勘定元帳、規制報告の根拠資料、KYC 書類、本人確認書類、金融商品取引の説明・同意証跡。
- オブジェクトストレージ、文書管理システム、電子帳簿保存法対応ストレージなど。

単なるファイルアップロード保存は Infrastructure / Repository 実装で足りることが多い。保存の成否、保管方式、改ざん困難性、保存証明が業務ルールに入るなら Port 候補になる。

### transaction / lock / idempotency が外部調整になる場合

複数の外部システムや DB 更新をまたぎ、二重実行や競合を Application が制御する必要がある場合。

- 決済、在庫、配送、会計の連携。
- 銀行振込、二重送金防止、残高拘束、同時出金、勘定系と周辺システムの整合性。
- 医療予約枠、検査依頼、受診受付の排他制御。
- 契約締結、承認ワークフロー、重複送信防止。

ただし、単一 DB の repository 保存を囲むだけなら、まず Infrastructure 側の実装や transaction script を検討する。Application に `TransactionRunner` を持ち込むのは、UseCase が複数 repository / 外部副作用の一貫性を明示的に要求する場合に限る。

### 外部通知と到達保証

通知自体が業務成立条件、または到達保証・再送・証跡が業務要件になる場合。

- 契約締結通知、医療予約通知、処方関連通知、決済通知、重要な行政・政治系通知。
- 振込受付通知、入出金通知、不正利用疑い通知、約定通知、重要事項変更通知。
- メール、SMS、郵送、外部メッセージング、Webhook。

単なるお知らせメールは Port より event handler / notification infrastructure でよい。通知の到達、既読、配達証明、法的通知性が業務判断になるなら Port 候補になる。

## 業種別の見方

- 基幹業務 / 基幹システム: 会計、在庫、受発注、与信、配送、マスタ連携、排他制御、idempotency が Port 候補になりやすい。
- 銀行業務 / 銀行システム: 勘定系連携、口座照会、残高拘束、振込、口座振替、与信、AML / CFT、制裁リスト照会、不正検知、営業日 / カットオフ判定、外部決済ネットワーク、規制報告、取引証跡が候補になりやすい。
- 医療業務 / 医療システム: 患者資格、医療者資格、EHR / EMR、検査、処方、医療文書署名、アクセス証跡が候補になりやすい。
- 大規模企業システム: SSO、権限基盤、組織マスタ、承認ワークフロー、DWH / ERP / CRM 連携が候補になりやすい。
- EC 業務 / EC システム: 決済、返金、在庫引当、配送、請求、クーポン適用、外部モール連携が候補になりやすい。
- 保険業務 / 保険システム: 契約引受、保険料計算、告知査定、外部医療情報照会、事故受付、損害査定、保険金支払、再保険連携、規制報告が候補になりやすい。
- 物流 / 航空 / 交通システム: 予約枠、座席 / 積載枠、運賃計算、配送ラベル、追跡、通関、外部キャリア連携、遅延通知、運行制御との境界が候補になりやすい。
- 公共 / 税務 / 行政システム: 住民・法人・資格情報照会、申請受付、審査、納付、給付、外部行政機関への届出、公開義務、法定保存、番号制度との連携が候補になりやすい。
- エネルギー / 通信インフラ: 検針、課金、利用量取得、開通 / 停止、設備管理、障害通知、外部制御システム、需給調整が候補になりやすい。
- 契約書 / 誓約書システム: 電子署名、本人確認、時刻証明、原本保管、締結証跡、閲覧証跡が候補になりやすい。
- 政治系業務 / システム: 本人確認、資格確認、期限判定、公開義務、届出、寄付適格性、改ざん困難な証跡が候補になりやすい。

## Port を使うケースパターン

### 参照系 Port

外部状態を読まないと判断できないが、その照会自体は副作用を持たない場合。

例:

- `CheckAccountBalance`
- `CheckSanctionsList`
- `CheckInsuranceEligibility`
- `FetchShipmentStatus`

Application は照会結果を使って判断する。照会先 API、cache、fallback、timeout は Infrastructure 側に閉じ込める。

### 命令系 Port

外部システムへ不可欠な副作用を発生させる場合。

例:

- `ExecuteBankTransfer`
- `CapturePayment`
- `SendPrescription`
- `SubmitRegulatoryReport`

業務操作の成否に直結するため、戻り値は単なる bool ではなく、受付番号、外部取引 ID、失敗理由、再試行可否などを表す result value を検討する。

### 証明系 Port

時刻、署名、保管、監査証跡など、あとから事実を証明するための外部能力を使う場合。

例:

- `IssueTimestamp`
- `SignContractDocument`
- `StoreImmutableEvidence`
- `RecordTransactionEvidence`

通常ログや audit log と違い、証明の失敗が業務成立に影響するなら Application の要求として扱う。

### 調整系 Port

二重実行、競合、外部システム間の整合性を制御する場合。

例:

- `ReserveAccountBalance`
- `AcquireBookingSlot`
- `EnsureIdempotentTransfer`
- `RunDistributedBusinessTransaction`

安易に汎用 `TransactionRunner` に寄せず、業務上何を拘束・確保・重複防止したいのかを名前に出す。

### 通知系 Port

通知の到達や法的通知性が業務判断に入る場合。

例:

- `SendLegalNotice`
- `SendFraudAlert`
- `SendContractCompletionNotice`

単なるメール送信は event handler / notification infrastructure でよい。到達証明、再送制御、既読、法的通知性が必要な場合だけ Port 候補にする。

業種名だけで Port を作らない。上の外部能力が、対象 UseCase の成立条件に入った時だけ Port 候補にする。

## 原則

Port は最終手段に近い。正確には、最後まで避けるものではなく、次の条件が揃ったときにだけ使う。

- Application がその能力なしでは業務判断または業務完了を表現できない。
- その能力の実体が Application 内に置けない外部世界、実行環境、暗号、時刻、I/O、外部システムである。
- 標準 contract、Repository、Query、event handler、Presentation / Infrastructure 配置では責務が説明できない。
- Interface 名が実装技術名ではなく、業務または Application の要求を表している。

この条件を満たさないなら、Port を作らない方がよい。

## Laravel の Contract との違い

Laravel の Contract の多くは、業務 Application の Port ではなく、フレームワーク機能の差し替え点である。Cache、Queue、Mail、Filesystem、Auth、Events などは、Laravel という実行基盤の拡張境界であり、Application のユースケースが外側へ要求する能力とは粒度が違う。

本プロジェクトでは Laravel 的に何でも Contract 化しない。Application が本当に外側へ要求している能力だけを Port にする。

## 判断手順

Port が必要か迷ったら、次の順で判断する。

1. その処理は Application のユースケース判断に必要か。
2. その処理の実体は Application に置いてよい純粋な計算か。
3. DB、ファイル、HTTP、外部サービス、ライブラリ、時刻、セッション、暗号など外側の都合を含むか。
4. Application が具象へ依存すると、依存方向が外側へ向かないか。
5. Interface 名は Application 側の要求を表しているか。

1 が false なら Application へ入れない。Presentation / Infrastructure / Framework 側で扱う。

2 が true なら Port は不要で、Application や Domain の value / service として実装できる。

3 と 4 が true なら Port を切る候補である。

5 が false なら、Port 名ではなく責務理解がまだ曖昧である。先に名前と責務を詰める。

さらに、標準 contract や既存 framework の interface で十分に境界が表現できている場合は、独自 Port を追加しない。独自 Port を切るのは、Application 側の語彙として別名を与える必然性がある場合に限る。

## このプロジェクトでの配置

Query interface は既存方針どおり、各 UseCase 配下の `Query` に置く。

Repository interface は Domain 配下の `Repository` に置く。

Query / Repository 以外の Port は、まず必要としている UseCase または機能コンテキストの Application 配下に小さく置く。最初から汎用 `Port` ディレクトリを作らない。

例:

```text
apps/cms/src/Application/Auth/Login/Port/IPasswordVerifier.php
apps/cms/src/Infrastructure/Auth/Password/NativePasswordVerifier.php
```

複数 UseCase から共有され、同じ Application コンテキストの能力として安定した場合だけ、より上位の Application 配下へ移動する。
