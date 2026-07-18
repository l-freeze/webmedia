# システム全体・技術アーキテクチャ総合レビュー

評価日: 2026-07-17
対象: `AGENTS.md`、`docs/` 全体、現行ソース・設定・テスト・DB定義・ローカル実行経路
状態: 改善候補をトリアージするための監査報告。現行仕様や規約そのものではない。

## 1. 結論

このシステムの中心的な意味は「記事を保存するCRUD」ではない。編集者がコンテンツを作り、公開可否と公開期間を決め、読者へ安全に配信し、問い合わせを受け、後から誰が何をしたか説明できる、小規模な編集・公開システムである。

現行実装は、Framework / Kernel / Shared / App、さらに Application / Domain / Infrastructure / Presentation の依存方向を明示し、自動テストでも形を守っている。この点は強い。一方、設計品質の重心が「ディレクトリ、Interface、Input / Interactor / Output、Value Object の形」に寄り、次の実行時意味論が相対的に弱い。

- 失敗時に処理が成功したのか失敗したのか。
- DB、ファイル、監査証跡のどこまでを同じ整合性境界にするのか。
- 同時編集や同時管理操作で、どの不変条件を守るのか。
- 公開GETを本当に状態なしで扱えるのか。
- 障害をどう検知し、どのリクエストまで追跡し、どう復旧するのか。
- 設定不足や外部CDN障害を起動時・リリース時にどう検出するのか。
- 要件、DB列、Application、UI、履歴、公開面が一つの機能として完結しているか。

したがって、次の改善ではレイヤーやファイルをさらに増やすより、品質属性を具体的なシナリオと受入条件に変え、実行時の契約を閉じる方が効果が高い。特にリリース前は、例外処理、設定検証、CSRF、Host / proxy trust、公開面のセッション、アップロード、問い合わせ濫用対策、バックアップ復元を先に閉じるべきである。

## 2. 今回の確認範囲と方法

### 2.1 確認したもの

- `AGENTS.md` と、リポジトリ内の運用指示。
- `docs/` の Markdown 17件、静的HTML 29件、CSS 2件、キャプチャスクリプト 2件、PNG/JPEG 62件。画像は4枚のcontact sheetで全画面を一覧確認し、重要画面は対応HTML/CSSとも照合した。
- `apps/`、`src/`、`config/`、`database/`、`public/`、`bin/`、`tests/`、`resources/` にある一次ソース、設定、テスト。生成済みの `public/assets/js/cms-rich-editor.js` と `tailwindcss-3.4.17.js` は逐語レビュー対象から外し、前者の入力ソースと両者の読み込み・CSP・配布方法を確認した。対象は813ファイル、約54,627行である。
- HTTP routing、認証・認可、記事/固定ページ保存と復元、問い合わせ、メディア、監査・Security Log、キャッシュ、worker lifecycle、DB接続の主要経路を手動で追跡した。
- Docker Compose上の実行結果、HTTPレスポンスヘッダ、現行DB制約を読み取り確認した。既存DBへの変更はしていない。
- リリース前であるため、schema定義のsquashと開発DBの作り直しを前提とした。過去のmigration履歴や手元DBとの差分は改善対象にせず、fresh installで得られる現行schemaだけを評価した。
- 作業開始前から存在した未追跡の `apps/minimal/`、`bin/memo.txt`、`docs/framework-quality-scoring.md`、`docs/franken-application-architecture.md`、`docs/php85-optimize.md` も評価対象に含めたが、変更していない。

### 2.2 実測結果

- PHPUnit: 214 tests / 3,177 assertions、成功。
- PHPStan level max: 735 files、成功。
- Composer audit: 既知の脆弱性なし。
- pnpm audit: 既知の脆弱性なし。
- 公開トップ、CMSログイン、robotsのHTTP応答を実測した。

これらは良いベースラインだが、現在のテストが主に単体テスト、構造テスト、限定的なSQLiteテストであることには注意が必要である。成功結果は、PostgreSQL上の競合、ファイルとDBの部分失敗、ブラウザE2E、アクセシビリティ、負荷、バックアップ復元、既知でない脆弱性を保証しない。

### 2.3 今回行っていないもの

- 本番環境、実トラフィック、運用組織、法的な保存期間の確認。
- 侵入テスト、DAST、ファジング、負荷・長時間・障害注入試験。
- スクリーンリーダーを含む支援技術での手動アクセシビリティ試験。
- 利害関係者を集めた正式なATAMワークショップ。

よって優先度は「このリポジトリをインターネット公開する前」という前提で付けている。実運用条件が決まったら、可用性、RPO/RTO、保存期間、最大ファイル、許容応答時間を具体値で再評価する必要がある。

## 3. 評価観点の根拠

レビューは既存docsの規約を採点表にはしていない。規約そのものが目的に合っているかも評価対象にした。

1. [ISO/IEC 25010:2023](https://www.iso.org/standard/78176.html) の9品質特性を全体の漏れ止めに使った。機能適合性、性能効率性、互換性、相互作用能力、信頼性、セキュリティ、保守性、柔軟性、安全性を、存在ではなく測定可能なシナリオとして見る。
2. [SEI Architecture Tradeoff Analysis Method](https://www.sei.cmu.edu/library/architecture-tradeoff-analysis-method-collection/) に従い、品質を抽象語のまま評価せず、刺激、環境、対象、応答、測定値で考え、セキュリティ対利便性、監査耐久性対応答成功などのtrade-offを確認した。
3. Parnasの[情報隠蔽によるモジュール分解](https://doi.org/10.1145/361598.361623)を使い、同じ手順を別ファイルにしたかではなく、変わり得る意思決定を隠せているかを見た。
4. Cockburnの[Ports and Adapters](https://alistair.cockburn.us/hexagonal-architecture)を使い、Interfaceの個数ではなく、UI・DB・batch・testなどの外部actorからApplicationの意味を独立させられるかを見た。
5. [NIST SSDF 1.1](https://csrc.nist.gov/pubs/sp/800/218/final)、[OWASP ASVS](https://owasp.org/www-project-application-security-verification-standard/)、OWASPの[File Upload](https://cheatsheetseries.owasp.org/cheatsheets/File_Upload_Cheat_Sheet.html)と[Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)を、secure-by-default、依存管理、秘密、session、upload、検証可能性の基準にした。
6. [PostgreSQL Constraints](https://www.postgresql.org/docs/current/ddl-constraints.html)を、アプリケーションの検証とは別に、永続データが単独でも不変条件を守れるかの基準にした。
7. Google SREの[Monitoring Distributed Systems](https://sre.google/sre-book/monitoring-distributed-systems/)と[OpenTelemetry specification](https://opentelemetry.io/docs/specs/otel/)を使い、ログファイルの存在ではなく、latency / traffic / errors / saturationと、logs / metrics / tracesの相関を見た。
8. [NIST SP 800-92](https://csrc.nist.gov/pubs/sp/800/92/final)を、監査・Security Logの生成だけでなく、転送、保護、保存、閲覧、廃棄までのlifecycleの基準にした。
9. [WCAG 2.2](https://www.w3.org/TR/WCAG22/)を、PC/SPの見た目だけでなく、名前・役割・状態、キーボード、focus、error identification、contrast、reflowの基準にした。
10. [DORA software delivery performance](https://dora.dev/guides/dora-metrics/)を、変更容易性をファイル数や抽象化の多さではなく、小さく安全に変更し、失敗を検知・復旧できる能力として見る基準にした。

## 4. システムの意味、境界、状態

```text
未信頼: Browser / Bot / uploaded file / HTTP headers
            |
            | HTTP(S), CDN script/style
            v
実行境界: Caddy + FrankenPHP worker
            |
            v
合成境界: Kernel -- path --> Front / Front Business / CMS
                               |       |              |
                               +-------+--------------+
                                       |
                            Shared / Framework ports
                                       |
                +----------------------+---------------------+
                |                      |                     |
        PostgreSQL（業務状態）  public/uploads（媒体） storage/logs/cache
```

### 4.1 本来区別すべき状態

- **業務上の正**: users、articles、versions、pages、分類、inquiries、settings。PostgreSQLが正である。
- **業務上の正に付随する媒体**: upload原本と派生画像。DB行だけでもファイルだけでも完結しない。
- **導出可能な状態**: `rendered_body_html`、`search_body_text`、画像派生、layout cache。再生成規約が必要である。
- **証跡**: Audit / Security Log。通常ログとは目的、改ざん耐性、保存期間、閲覧権限が異なる。
- **一時状態**: session、CSRF token、file cache。公開面全体の意味をstatefulに変えないよう適用範囲を限定すべきである。

### 4.2 現在の良い点

- FrontからCMSへの依存、ApplicationからInfrastructureへの依存などを構造テストで禁止している。
- PDOの値は原則placeholderで渡され、Response headerの改行と名前も検査される。
- 公開判定の通常FrontとCMS dashboardは `IClock` を使い、公開日時のJST入力/UTC保存も明示されている。
- Markdown / HTML / Tiptapは保存時にsanitizeされ、Tiptapのテキスト・属性はescapeされる。
- Sessionはstrict mode、cookie only、HttpOnly、SameSiteを設定し、ログイン成功時にIDを再生成する。
- uploadはMIME allowlist、ランダム保存名、画像pixel上限、外部コマンドの引数配列・timeoutを持つ。
- Audit、Security Log、Application Logの概念を分け、request bodyやpasswordをAudit metadataへ丸ごと保存していない。
- worker requestごとにapp containerを作り直し、Session / CSRFなどをworker singletonにしない方針は妥当である。

## 5. 優先度

| 優先度 | 意味 |
|---|---|
| P0 | インターネット公開前に閉じる。秘密漏えい、認証回避、データ損失、復旧不能、結果不明を避けるrelease gate |
| P1 | 最初の安定運用までに閉じる。競合、整合性、監査、性能劣化、運用不能を避ける |
| P2 | 機能拡張前に整理する。変更コスト、認知負荷、要件drift、テスト不足を抑える |
| P3 | 継続改善。局所的な一貫性、開発体験、説明品質を上げる |

## 6. P0: リリース前の改善点

### P0-01 例外処理と通常ログが実行経路として成立していない

- 根拠: `apps/kernel/config/application.php:32` は `RethrowingExceptionHandler` を標準にし、`APP_DEBUG` は読み込まれない。PSR-3 loggerは `apps/shared/config/container.php:79-82` に登録されるが、実処理からのlogger呼び出しは確認できない。
- 意味: 500時の本文、秘匿、request ID、例外記録、通知がCaddy/PHPの暗黙動作へ委ねられる。障害時に「何が、どのrequestで、どのuser操作により失敗したか」を一貫して追えない。
- 改善: production用 `IExceptionHandler` をKernelで合成し、外部には固定500、内部には生成したrequest ID、route、例外class、stack、actor ID、worker/process情報を構造化記録する。`APP_DEBUG`を使うなら意味を定義し、使わないなら削除する。
- 完了条件: 例外を起こすHTTP integration testで、秘密を含まない500、同一request IDのresponse/log、1件のerror metricが確認できる。

### P0-02 初期credentialと必須secretがfail-safeではない

- 根拠: `database/seeds/InitialDataSeeder.php:13-22` は `admin@example.com / password` を無条件に作る一方、READMEは `admin@example.test`、`.env.example`の `CMS_DEFAULT_*` はseedから参照されない。`CMS_LOGIN_ATTEMPT_HASH_KEY` は未設定時に `APP_URL`、さらに `webmedia` へfallbackする (`apps/cms/config/container.php:155-157`)。
- 意味: seedを誤って公開環境で実行すると既知credentialが入り、設定したつもりの値と実値もずれる。予測可能なHMAC keyはDB流出時のメール候補照合を容易にする。
- 改善: seedを `APP_ENV=local|test` でのみ許可し、初期userは明示コマンドでランダムpasswordを一度だけ発行する。productionではsession secure、site URL、login hash key、DB secretなどを起動時に検証し、fallbackしない。
- 完了条件: production相当環境でseedが拒否され、弱い/未設定secretで起動せず、READMEの手順がfresh installで再現する。

### P0-03 CSRFを環境名のdenylistで無効化している

- 根拠: SharedとCMSのcontainerは `stg`、`staging`、`dev`、`development`、`test` などでCSRFを無効化する (`apps/shared/config/container.php:69-75`, `apps/cms/config/container.php:127-134`)。
- 意味: stagingが外部公開・本番データ接続・共有cookie domainになった場合、環境名だけで防御が消える。開発環境もブラウザとネットワークを持つ以上、安全とは限らない。
- 改善: CSRFは既定で常時有効にする。テストだけはDIで明示的なfake/middleware差替えを行い、path除外は個別の脅威分析とテストを必須にする。
- 完了条件: local/staging/productionの状態変更がtokenなしで403になり、テスト無効化が本番configから到達不能である。

### P0-04 Host / proxy / TLSのtrust boundaryが未定義

- 根拠: `Request::isHttps()` は送信元を限定せず `X-Forwarded-Proto` を信用する (`packages/framework/src/Http/Request.php:91-99`)。`FRONT_SITE_URL` が空ならrobots/sitemapが `HTTP_HOST` から絶対URLを作る。実測で `Host: attacker.example` に対し `Sitemap: http://attacker.example/sitemap.xml` が返った。
- 意味: Host header poisoning、誤canonical、cookie secure判定の誤りにつながる。proxyを置いた時だけでなく、直結時にforwarded headerを外部から注入できることも問題である。
- 改善: productionでは公開base URLを必須化し、許可HostをCaddyとApplicationの両方で制限する。trusted proxy CIDRと、どのforwarded headerを正規化するかをCaddy側に閉じ、Applicationは正規化済み属性だけを見る。
- 完了条件: 未許可Hostは400、外部からの偽 `X-Forwarded-Proto` は無視され、robots/canonical/cookie属性が構成表どおりになる。

### P0-05 公開GETが常にsessionを開始し、cache不能になっている

- 根拠: Kernelはapp判定前に全requestで `session_start()` する (`apps/kernel/src/Application.php:69-81`)。実測で `/` と `/robots.txt` に `PHPSESSID`、`Cache-Control: no-store, no-cache` が付いた。
- 意味: 読者向け公開面が不要にstatefulになり、CDN/browser cache、匿名性、性能、容量計画を損なう。robotsやsitemapまでsessionを持つ意味はない。
- 改善: Sessionを必要なroute/appでlazy startする。Frontでは問い合わせフォームのCSRFなど必要箇所だけに限定し、記事・一覧・robots・sitemapはsessionlessにする。公開responseのCache-Control、ETag/Last-Modified、purge条件を決める。
- 完了条件: 匿名GETにはSet-Cookieがなく、公開contentは定義したcache policyを持ち、CMSと問い合わせだけがsessionを開始する。

### P0-06 uploadの可用性・保存境界が公開運用向けでない

- 根拠: 動画上限は512MiBで、Dockerの `upload_max_filesize` も512M。現在のignored uploadには326MiB AVIを含む約349.8MiBがある。ファイルをwebrootへmoveし派生生成後にDB insertするため、DB失敗時にorphan fileが残る。動画は直接公開される (`apps/cms/src/Infrastructure/Persistence/PdoMediaRepository.php:20-111`)。
- 意味: 少人数CMSでも、誤操作や侵害アカウントによりdisk、worker、networkを飽和できる。DB backupだけでは媒体を復元できず、途中失敗でDBとfilesystemが分岐する。
- 改善: 実際の業務要件まで形式と容量を縮め、Caddy/request/body、PHP、Application、storage quotaの多層上限を設ける。原本はwebroot外/object storageへ置き、scan/metadata検査後に公開する。uploadをstaging pathへ置き、DB成功後に確定、失敗時cleanupする。大動画処理は同期workerから分離する。
- 完了条件: 上限超過を早期拒否し、disk quota/timeout/partial failure試験でorphanが残らず、媒体をDBと一緒に復元できる。

### P0-07 問い合わせが無制限に永続化できる

- 根拠: `/contact` は形式・長さ・CSRFを検証するが、rate limit、bot対策、重複抑止、容量制限、通知、retentionがない (`apps/front/src/Application/Inquiry/Create/Interactor.php`, `PdoInquiryRepository.php`)。
- 意味: CSRFはbotや直接POSTを止めない。DB増加、管理者の対応負荷、個人情報の過剰保持、通知されず見逃すリスクがある。
- 改善: IP/時間帯と全体量のrate limit、honeypotまたはchallenge、本文の正規化・重複検知、最大DB成長量、運用通知、削除/匿名化期間、privacy文面を一つの機能として設計する。
- 完了条件: burst/分散濫用の期待応答、保存/非保存、security event、retention job、管理者通知をintegration testで確認する。

### P0-08 backup / restore / deploy / rollbackが未成立

- 根拠: `docs/feature_inventory.md` も未実装としているが、ソース上もDB+uploads+logsの一貫したbackup、restore test、release health check、rollback手順がない。
- 意味: backupが「ある」だけでは復旧可能性を保証しない。DBと媒体の時点がずれると記事は復元しても画像が欠落する。監査ログを同じ保管方針にするかも未決定である。
- 改善: RPO/RTOを決め、PostgreSQL、upload原本、暗号鍵/設定、必要なAuditを対象にしたbackup、暗号化、世代、off-site、定期restore drillを定義する。リリース後にschema変更を始める時点で、expand/contractとforward fix/rollbackの境界を決める。
- 完了条件: 空環境への自動restoreで代表記事・画像・user・settingsが一致し、所要時間と欠落許容がRPO/RTO内に収まる。

### P0-09 browser supply chainとCSPの所有者が二重化している

- 根拠: FrontはunpkgからHTMXとversion未固定のStimulusを読み、CMSはjsDelivrのflatpickrをSRIなしで読む。App側Front CSPはinline scriptを許さないが、Caddyがより広い `unsafe-inline` + unpkg + jsDelivrのCSPを全responseへ設定する。実測responseもCaddy側の広いCSPだった。
- 意味: Appの安全なpolicyが実運用では効かず、外部配布元の障害・改ざん・予期しないupgradeが実行コードに入る。CMSとFrontで必要なpolicyも異なる。
- 改善: browser assetをbuildに取り込みlockfileからself-hostするか、厳密version+SRIを使う。CSPの唯一のownerを決め、app/route別policy、nonce/hash、`object-src 'none'`等を自動testする。Tailwind browser runtimeはbuild済みCSSへ置換する。
- 完了条件: production HTMLが未固定外部scriptを参照せず、実HTTP responseのCSPが期待値と完全一致する。

## 7. P1: 整合性・運用安定性の改善点

### P1-01 DBが業務不変条件をほとんど保持していない

- 根拠: 初期schemaにはPK/unique indexはあるが、Foreign KeyとCHECKがない。現DBのconstraint読み取りでもPK以外の関係制約は確認できなかった。
- 影響: category、author、article_tags、versions、login reset userのorphan、未知status/role/body format、負のversion/order/size、矛盾した公開期間をDBが受け入れる。別adapter、保守SQL、bugがApplication validationを迂回できる。
- 改善: delete意味を決めた上でFK (`RESTRICT` / `SET NULL` / `CASCADE`) を追加し、enum候補、version正数、file size、公開期間などをCHECKにする。Application validationは利用者向けerror、DB constraintは最後の防壁として併用する。
- 完了条件: 不正fixtureをDBへ直接入れるintegration testがconstraint violationになり、全delete use caseの期待動作が明文化される。

### P1-02 `site_settings` のsingleton不変条件とsaveが競合に弱い

- 根拠: tableはID/PK/unique keyを持たず、readは `LIMIT 1`、saveは全行UPDATEして0行ならINSERTする (`apps/cms/src/Infrastructure/Persistence/PdoSettingRepository.php:18-105`)。
- 影響: 空tableへの同時saveで複数行ができ、複数行になれば全行更新され、どれを読むかは未定義になる。
- 改善: `id=1` などの固定PKまたはsite keyを持たせ、`INSERT ... ON CONFLICT DO UPDATE` でatomic upsertする。将来site modelを導入するなら、その時点でsite単位uniqueへ移行する。
- 完了条件: 10並列save後も対象keyが1行で、read結果が決定的である。

### P1-03 「最後のadmin」不変条件にTOCTOU raceがある

- 根拠: Update/Delete Interactorは `roleOf()`、`adminCount()`、`update/delete()` を別query・transaction外で実行する。
- 影響: 二人のadminが同時に互いを降格/削除すると、双方がcountを通過しadminが0人になり得る。
- 改善: repositoryに「最後のadminを残して更新/削除する」という目的のoperationを置き、transaction+row/advisory lockで判定と更新を一体化する。単なる `adminCount()` portでは不変条件が隠蔽されない。
- 完了条件: 並列transaction testで必ず1人以上が残り、敗者は業務errorを受ける。

### P1-04 記事・固定ページはlast-write-winsで編集競合を失う

- 根拠: saveは行を `FOR UPDATE` してversion番号を採番するが、画面表示時の `current_version_no` を期待値としてUPDATE条件に使わない。
- 影響: 二つのtabや二人の編集者が保存すると、後保存が先保存を無言で上書きする。履歴には残っても、利用者は競合を認識できない。
- 改善: edit formへexpected versionを載せ、`UPDATE ... WHERE id=? AND current_version_no=?` とする。0行なら差分/再読込/上書き選択を返す。自動保存を入れる前に必須である。
- 完了条件: 二並列editの一方だけが成功し、他方に競合と比較対象versionが示される。

### P1-05 EventBusはPub/Subではなく同期dispatcherで、失敗意味論が逆転する

- 根拠: `SynchronousEventBus` は同一call stackでhandlerを順次呼び、例外をそのまま伝播する。AuditMiddlewareはcontroller/DB処理後にdispatchする。READMEは「実運用ではAudit保存失敗だけで完了済みresponseを壊さない」とするが、現実装とtestは例外可視化を要求している。
- 影響: DB commit後にaudit file writeが失敗すると、利用者には500でも業務状態は変更済みになる。login成功後のSecurity Log失敗もsession設定後に失敗し、logoutはlog失敗でsessionを破棄しない。retryで重複や誤認が起きる。
- 改善: まず名前と契約を分ける。現在のものは `InProcessEventDispatcher` とし、handler順序、失敗隔離、再入、idempotencyを定義する。業務成功を優先するAudit/Security telemetryはbest effort+必ずerror metric、証跡を業務成立条件にする操作だけは同一DB transactionまたはoutboxへ置く。小規模構成にbrokerを先に入れる必要はない。
- 完了条件: disk full時のsave/login/logout/CSRF failureごとに、業務結果、HTTP結果、証跡欠落通知が仕様どおりで、結果不明がない。

### P1-06 Audit / Security / Application Logにlifecycleがない

- 根拠: JSON Linesを同一diskへappendするだけで、rotation、retention、容量上限、権限分離、改ざん検知、外部転送、backup、廃棄がない。Audit UIはファイル全体をmemoryへ読みsortし、listとcountで再走査する。
- 影響: 長期運用でdisk/memory/応答が悪化し、侵害者がアプリと同じ権限で証跡を改変できる。IP、user-agent、actor、metadataの個人情報保持期間も不明である。
- 改善: log classごとにowner、schema version、PII、retention、access、rotation、compression、integrity、export、write failure policyを決める。検索要件があるAuditはDB/append-only storeへ移しindexとpaginationを持たせる。
- 完了条件: 保持期間超過、disk逼迫、破損行、巨大log、権限違反を試験し、alertとdegraded behaviorを確認する。

### P1-07 request correlation、metric、SLOがない

- 根拠: request IDは一部で `X-Request-ID` をそのまま記録するだけで、生成・形式検証・response返却がない。HTTP latency/error、DB time/query count、upload time/failure、cache hit、login block、inquiry rateのmetricがない。
- 影響: 既存loggerを追加しても、障害の検知と相関ができない。平均ではuploadやsearchのtail latencyも見えない。
- 改善: edgeまたはKernelでrequest IDを生成/検証し、response、log、auditへ伝播する。最小metricはroute別traffic/error/latency histogram、worker memory、DB pool/query、disk/upload、cache hit、security countersとする。可用性・latencyのSLI/SLOを先に小さく決める。
- 完了条件: synthetic failureをdashboardからrequest logへ辿れ、SLO違反だけがactionable alertになる。

### P1-08 ファイルとDBを跨ぐMedia operationの補償が片側だけである

- 根拠: uploadはfile/variantを先に作りDB insertするが例外cleanupがない。deleteはDB commit後に `unlink()` し、失敗を検査しない。本文中の画像参照は追跡せず削除できる。
- 影響: orphan file、DBが指すmissing file、本文のbroken imageが生じる。記事versionのeyecatchをNULLにするため、過去版も元状態を再現できない。
- 改善: mediaをstate machine (`staged` / `ready` / `deleting` / `failed`) として扱うか、補償jobを持つ。参照はeyecatchとrich textを含めてindex化し、使用中削除を拒否/置換/論理削除のどれにするか決める。versionの再現性要件も決める。
- 完了条件: 各stepへの障害注入後、reconciliation jobでDB/filesystemが収束し、使用中媒体はpolicyどおりになる。

### P1-09 認可が記事以外ではHTTP routeへ偏っている

- 根拠: 記事だけはactorをInputへ渡し `ArticlePolicy` を呼ぶ。Page、Settings、Taxonomy、Media、Inquiry更新などはroute metadataのpermissionに依存し、UseCase単独ではactor/permissionを要求しない。
- 影響: 将来のCLI、batch、API、test helperから同じInteractorを呼ぶと認可を迂回する。docs自身もこの原則を認識するが展開が未完了である。
- 改善: 「routeへ入れるか」と「対象操作を実行できるか」を分け、機密度の高いcommand use caseはActor/PolicyをApplication境界で必須にする。全CRUDへ機械的なpermission classを増やすのではなく、業務上差がある操作から行う。
- 完了条件: HTTPを通らないuse case testでもviewer/writer/editor/adminの許否が同じになる。

### P1-10 Article capabilityは構造的保証になっていない

- 根拠: permission valueの生成箇所を文字列scan testで制限する一方、permission classのconstructorと `Article` constructorはpublicである。`Article::create()` のpermission引数は使用されず、直接 `new Article(...)` で迂回できる。
- 影響: 「Policyだけが権限を発行しEntityが強制する」という説明とPHPの実際の境界が一致しない。形だけのcapabilityが安全性の誤認とファイル増加を生む。
- 改善: 本当に構造で守るならconstructor visibility/namespace factoryを設計し直す。PHPでその保証が不自然なら、認可はApplication Policyの明示的な結果として集中し、未使用permission引数を削除する。どちらか一方にする。
- 完了条件: Policyを通らないpublished/create/updateを実行できないbehavior test、または単純化後の単一認可経路がある。

### P1-11 login/session防御の運用要件が不足する

- 根拠: email+IP/IP rate limit、generic error、session regenerationはある。一方、password rehash、idle/absolute timeout、high-risk reauthentication、全体/email単位の分散試行対策、試行履歴cleanupがない。password最小長は8固定である。
- 影響: 分散IP攻撃、長寿命session、古いhash設定、試行tableの無制限増加に弱い。小人数CMSではMFAまたは外部IdPの費用対効果も高い。
- 改善: threat modelに基づき、長いpassword/passphrase、`password_needs_rehash()`、idle/absolute timeout、管理者変更時再認証、MFA/IdP、global/email/IP多層rate limitとretentionを設計する。
- 完了条件: session expiry、hash upgrade、分散試行、管理者高リスク操作をsecurity testで確認する。

### P1-12 File cacheが信頼し過ぎでatomicではない

- 根拠: `FileSimpleCache` は `unserialize(... allowed_classes => true)` し、最終pathへ直接writeする。readerはlockを取らず、破損時はsilent missになる。
- 影響: cache directoryへのwrite primitiveがある場合にobject injection面を増やし、並行read/writeでpartial record、stampede、原因不明のmissが起き得る。
- 改善: 現用途が配列ならJSON等の非object形式に限定し、temp file+atomic rename、schema/version prefix、corruption metricを持つ。必要ならsingle-flight/lockを追加する。
- 完了条件: 並行read/write、途中kill、改ざんpayloadで安全なmissとtelemetryが得られる。

### P1-13 CORSが設定次第でcredential付き全Origin許可になる

- 根拠: allowed originsに `*` がありcredentials=trueなら、受信Originをそのまま `Access-Control-Allow-Origin` に反射してcredentialsも許可する。
- 影響: 誤設定一つでcredential付きcross-origin accessを広く許す。現状のHTML appにCORSが必要な理由も明示されていない。
- 改善: CORSが不要ならmiddlewareを外す。必要なら起動時に `* + credentials` を拒否し、正規化したexact origin、method、headerだけを許可する。
- 完了条件: 許可/未許可/preflight/wildcard+credentialsをconfig testとHTTP testで固定する。

### P1-14 DB row変換が破損を正常値へ変える

- 根拠: `PdoRow::int/string/date` はmissing/invalid値を0、空文字、defaultへ黙って変換する。多くのQuery/Repositoryがrequired列にもこれを使う。
- 影響: schema drift、破損、query alias typoが即失敗せず、id=0、空title、既定statusとして画面へ出る。異常検知が遅れ、別データを壊し得る。
- 改善: required accessorはmissing/type不正でmapping exception、nullable/default accessorは明示名に分ける。外部表示のfallbackと永続化境界の厳密性を分離する。
- 完了条件: required列欠落fixtureが例外+correlated logになり、nullable列だけがnullを許す。

### P1-15 時刻の規約が一部だけで、同じ事実を異なる時計で扱う

- 根拠: 通常Front/CMSの公開判断は `IClock` へ整理されたが、多数のRepositoryは `date()`、logは `gmdate()`、`PdoRow::date()` はprocess timezone、Front BusinessはSQL `NOW()` を使う。timestamp列はtimezoneなしである。
- 影響: container timezone変更、DST、test、Front/Front Business間で保存/表示/公開判断がずれる。
- 改善: 「instantはUTC」「編集UIはAsia/Tokyo」「DB型とserialization」を一つのtime policyにし、保存時刻もClock portまたはDB clockのどちらかへ統一する。POCを公開し続けるなら例外扱いをやめる。
- 完了条件: process/DB timezoneを変えたintegration testでも同一instantの公開、表示、auditが一致する。

### P1-16 search/listがデータ増加を境界として持たない

- 根拠: Front searchは `ILIKE '%keyword%'` でlimit/paginationなし。category/tag記事一覧も全件返す。公開判定向けの複合index、全文検索indexもない。
- 影響: 記事数やbot query増加に比例してDB/response/memoryが増え、workerを占有する。平均件数が小さい間は問題が隠れる。
- 改善: 全collection portに最大件数/cursorまたはpageを定義し、PostgreSQL full-text/trigramのどちらを使うか日本語要件と測定で決める。公開条件+sortに合うindexを `EXPLAIN ANALYZE` で選ぶ。
- 完了条件: 想定最大件数でp95目標、query plan、最大response sizeを満たすload testがある。

### P1-17 Front Businessの意味上の境界が試作のまま公開compositionへ入っている

- 根拠: docsはFront BusinessをPOC/試作として扱うが、Kernelは常に `/business` に組み込む。通常Frontとほぼ同数のPHPファイルを複製し、固定category slug、固定site settings、別clock規約を持つ。
- 影響: 「別app」「別site」「同じcontentの別presentation」のどれなのかが曖昧で、公開条件、検索、問い合わせ、SEO、修正を二重実装する。category名変更が公開先変更という隠れた業務操作になる。
- 改善: 製品面ならbounded context/siteとしてowner、URL、content selection、settings、SLOを正式化する。単なるtheme/segmentなら共通read model+policyへ寄せる。試作ならproduction compositionからfeature flagで外す。
- 完了条件: category変更時の公開先、通常Frontとの共有/分離、問い合わせ保存先、canonicalを利用者が一文で説明でき、contract testで固定される。

### P1-18 production構成が開発用Composeの延長になっている

- 根拠: Composeは固定DB credential、`APP_ENV=local`、`APP_DEBUG=true`、HTTP/auto_https off、bind mountを前提にする。image tagやCDN、build artifactのprovenance、read-only filesystem、health/readiness、graceful worker restart、secret injectionのproduction定義がない。
- 影響: 開発で動くことと、安全に再現・昇格・rollbackできることが混同される。
- 改善: production artifactとSBOM、digest pin、non-root/read-only、secret source、TLS/proxy、health/readiness、fresh schema適用、worker drain、deploy smokeを別profile/runbookとして定義する。
- 完了条件: 同じimmutable artifactをstagingからproductionへ昇格し、configだけを変え、health failureで自動停止できる。

## 8. P2: 変更容易性・テスト容易性・要件整合性の改善点

### P2-01 規約がarchitecture invariantと局所styleを混在させる

- 根拠: `webmedia_architecture_rules.md` は依存方向や境界だけでなく、全Entity propertyのValue化、全UseCaseのInput/Interactor/Output、named arguments、配列関数禁止に近い規則まで持つ。
- 影響: 重要度の違う規則が同じ強さになり、ルール適合が設計品質の代理指標になる。単純処理の変更差分と認知負荷が増える。
- 改善: 規約を (A) security/data invariant、(B) dependency boundary、(C) 推奨pattern、(D) styleに分ける。A/Bだけを原則CI gateにし、例外手続と削除条件を置く。
- 完了条件: 各規則が守るfailure scenarioと、自動化すべき強度を説明できる。

### P2-02 Value Objectの多くが意味や不変条件を持たない

- 根拠: CMS Domain Value 63件のうち50件以上が約12行のreadonly wrapperで、email、slug、日時、path、ID等もconstructor validation/behaviorを持たない。
- 影響: invalid stateを作れるまま、変換・ファイル・mappingだけが増える。型名による説明効果はあるが、全propertyへの一律適用は費用が勝つ。
- 改善: 単位、形式、不変条件、比較、正規化、誤取り違えリスクがある値だけをVOにする。単なるread model値はtyped DTO/primitiveを許し、IDも集約間誤用を実際に防ぐ箇所へ限定する。
- 完了条件: 各VOが「防ぐbug」または「持つ振る舞い」を答えられ、答えられないwrapperは統合候補になる。

### P2-03 UseCase tripletを全actionへ強制し、portsの目的より形が先行する

- 根拠: 構造testは第二階層directoryすべてに `Input.php / Interactor.php / Output.php` を要求する。引数なし一覧や単純readも同じ形になる。Cockburnのportは目的あるconversationであり、InterfaceやDTOの個数ではない。
- 影響: 小さな変更で複数ファイルが増え、navigate/search/review負荷が上がる。差し替え可能性のないQuery Interfaceも増え得る。
- 改善: command、複雑query、外部境界には明示use caseを維持し、引数なし/単純readは一つのapplication service/valueへまとめる選択を許す。port作成条件を「別actor/adapter/test doubleが必要」「domainをI/Oから守る」に戻す。
- 完了条件: 代表機能で変更ファイル数・test差替え・説明性を比較し、規約を実証結果で更新する。

### P2-04 Safe HTMLがただのstringとして境界を越える

- 根拠: sanitize済みHTMLはstringでQuery valueへ入り、Latteで `|noescape` される。安全性はRepository/Markdown実装を知っていることに依存する。
- 影響: 将来別Queryやimportがunsanitized stringを同じfieldへ入れても型・templateから区別できない。保存済みcacheがsanitizer version変更に追随する規約もない。
- 改善: `SafeHtml`/trusted content typeまたはrenderer専用APIで危険なsinkを明示し、生成者をsanitizerに限定する。sanitizer policy versionとrebuild commandを持つ。
- 完了条件: raw user HTMLをnoescape sinkへ渡すtestが型/静的検査で失敗し、policy更新時に全導出HTMLを再生成できる。

### P2-05 SEO/分類/履歴がschema・要件・機能で分断される

- 根拠: articles/pagesの `noindex`、categories/tagsのSEO列はschemaにあるが、CMS save/domain/repository/public headで完結していない。記事編集にtag保存がなく、version snapshotにもtag/noindexがない。OGP/Twitter/JSON-LDもUI要件とPresentation READMEにはあるが実装されていない。
- 影響: 「列がある」「画面モックにある」「要件にある」が完成の錯覚を作る。復元しても同じ公開状態にならない。
- 改善: capabilityごとに schema → Domain/Application → CMS UI → history/audit → Front/SEO → test の縦sliceで完成させる。未完成列は使うまで削るかexperimentalとして明示する。
- 完了条件: feature inventoryがコード存在でなく、縦sliceのacceptance testへlinkする。

### P2-06 Page restoreが存在しない版でも成功を返す

- 根拠: Repository `restore()` はversionがなければreturnし、Interactorは常に `restored: true` を返す (`apps/cms/src/Infrastructure/Persistence/PdoPageRepository.php:60-69`, `apps/cms/src/Application/Page/Restore/Interactor.php:17-23`)。
- 影響: 監査、flash message、利用者認識が事実と異なる。Article restoreとは契約も違う。
- 改善: repositoryはversionを返す/NotFoundを返し、Interactorが結果を決める。Article/Pageのrestore contractと監査actionを揃える。
- 完了条件: missing page/versionは404または業務errorとなり、成功Auditを出さない。

### P2-07 cache invalidationとfreshness契約がTTLだけで暗黙

- 根拠: layout globalsは固定keyで120秒cacheし、settings/category/page更新時にinvalidateしない。FrontとFront Businessは別keyだが、どの変更がどのcacheへ影響するかの所有者がない。
- 影響: CMS保存成功後もしばらく公開面が古く、利用者は失敗と誤認する。将来TTL延長や複数node化で差が広がる。
- 改善: 許容stalenessを明記し、短TTLで十分ならCMSに「最大N秒」を表示する。即時性が必要なら成功後のdomain/application eventで関係keyをinvalidateし、event失敗policyを定める。
- 完了条件: settings/category/page更新から公開反映までの上限がtestで保証される。

### P2-08 test suiteが構造適合に比べ、失敗・競合・実DBを薄くしか見ない

- 根拠: 214 testsは有用だが、architecture testの多くは文字列scan/ファイル存在検査である。Front BusinessはInterface naming testのみ。coverage/mutation threshold、PostgreSQL repository test、HTTP browser E2E、並列test、障害注入がない。
- 影響: public constructorでのcapability迂回、transaction race、Caddy header、session cookie、actual SQL、JS editor、file partial failureを見逃す。
- 改善: test portfolioを unit / contract / PostgreSQL integration / HTTP / browser E2E / security / accessibility / load / recovery に分け、各riskへ対応付ける。coverageは目標ではなく未観測risk発見に使い、重要Domainにはmutation testも検討する。
- 完了条件: P0/P1の各findingに少なくとも一つfailure-first testが紐づく。

### P2-09 accessibilityを要件・CI・手動確認のいずれでも閉じていない

- 根拠: 静的モックは見た目のPC/SPを持つが、divの検索欄、linkで代用したmenu control、labelとinputの明示関連、table caption/scope、live region、focus/error summary、reduced motionなどが体系化されていない。CMSのモバイルcontact sheetは固定sidebarが大きな幅を占める。
- 影響: キーボード、screen reader、200% zoom、狭幅、色覚で操作不能になり得る。自動testだけでも、画像だけでも検出できない。
- 改善: WCAG 2.2 AAを目標に、semantic HTML、focus order/visible、accessible name/state、error association、44px target、reflow/contrastをcomponent acceptanceへ入れる。axe等の自動testと、keyboard/screen reader手動checklistを併用する。
- 完了条件: login、article edit、media picker、contact、mobile navの代表journeyが自動+手動checkを通る。

### P2-10 docsの所有権と時間軸が分散している

- 根拠: `current_status.md` が長い変更履歴、`feature_inventory.md` が実装詳細、`plan.md` が将来、`architecture_boundary_review.md` が過去判断を持ち、同じ話題が `decisions.md`、architecture rules、technical stackにも重複する。`decisions.md` はworkerを標準とする一方、technical stackには通常modeに関する異なる説明がある。
- 影響: 新しい人は「今の正」を探すために多数文書を読む。更新漏れが仕様差より文書差を生む。
- 改善: 文書typeを current state / ADR / requirements / runbook / backlog / reference に限定し、各事実のownerを一つにする。`current_status` は再開に必要な短い状態、完了履歴はGitへ任せる。この報告もtriage後にplan/decisionsへ移し、残件がなくなれば削除する。
- 完了条件: 各質問（現機能、次作業、理由、運用方法）に一つのcanonical文書で答えられる。

### P2-11 根拠のない数値採点と人格評価を技術文書から除く

- 根拠: 未追跡の `docs/framework-quality-scoring.md` とFront Presentation READMEは、再現可能な測定なしに「+10〜15点」等を記載する。前者末尾には第三者作者への侮辱表現がある。また同文書はMarkdown/Tiptap内部を未読としながら品質総合を断定する。
- 影響: 評価の再現性とレビュー文化を損ね、外部公開時の法務・評判リスクになる。数値が優先順位を誤誘導する。
- 改善: 人物ではなく観測可能なartifactとscenarioだけを評価する。scoreを使う場合は尺度、weight、evidence、unknown、再現script、評価者間差を示す。侮辱表現は削除する。
- 完了条件: 全評価文がevidenceと反証条件を持ち、人物の能力断定を含まない。

### P2-12 role要件が2roleと4roleでdriftしている

- 根拠: UI要件はadmin/editor中心だが、実装はadmin/editor/writer/viewerとresource-level article policyを持つ。権限一覧画面も追加されている。
- 影響: これは実装拡張か要件変更かが不明で、受入test、UI表示、運用説明、最小権限がずれる。
- 改善: 現在必要なactorと責任を決め、role-permission matrixを唯一の要件にする。不要roleは削り、必要なら各roleの代表journeyとdeny testを持つ。
- 完了条件: 要件、CodeDefinedRolePermissionQuery、route、Policy、UI、testのmatrixが一致する。

### P2-13 design-only routeがproduction surfaceに残る

- 根拠: `/loading`、`/no-articles`、`/menu` とBusiness側同等routeが常時公開される。初期静的モックの状態確認が実App routeへ残っている。
- 影響: SEO crawl、利用者混乱、不要な保守/攻撃面を増やす。
- 改善: component catalog/dev-only appへ移すか、実状態をquery parameterやfixtureでE2E生成する。残すならnoindex、目的、SLOを正式化する。
- 完了条件: production route listに製品機能だけが存在する。

### P2-14 `apps/minimal` は説明とruntime contractが一致しない

- 根拠: 未追跡のMinimalは `App\Minimal` がComposer autoloadに登録されずKernelにも組み込まれない。独自SQLite PDOを作り、GETごとにDDLとINSERTを行う。
- 影響: starter/scaffold例として使うと、動かないか、KernelのPDO/冪等GET/production boundaryと異なる学習を促す。
- 改善: experimentならrepository外へ置く。公式sampleならautoload、ApplicationDefinition、DB ownership、GET安全性、testを一つのinstallable sliceとして完成させる。
- 完了条件: fresh checkoutから明示コマンドだけで起動し、同じGETを繰り返して状態を変更しない。

### P2-15 Framework coreとscaffoldの保証境界が未確定

- 根拠: Frameworkは`packages/framework`へ自己完結したComposer packageとして分離され、暗黙生成するinstaller pluginとPHPStan除外は削除済み。一方、runtime codeと明示scaffold binaryは同じpackageに含まれ、packaged artifactからの隔離E2Eとそれぞれのsupport範囲はまだない。
- 影響: HTTP runtimeの互換性保証と、生成Kernel / Shared / appの完成保証を同じものと誤解しやすい。
- 改善: 一つのpackageを維持する間も、runtime public API testとscaffold consumer E2Eを分け、support対象をREADMEとrelease policyで明記する。依存またはrelease cadenceが実際に分かれた場合だけscaffoldを別package化する。
- 完了条件: runtimeとscaffoldそれぞれのsupported PHP/runtime、security boundary、release testが説明でき、空consumerで生成後の最小HTTP応答まで再現できる。

### P2-16 未使用/重複dependencyとassetを定期的に棚卸しする

- 根拠: ComposerにはIntervention ImageとSymfony Mailerがあるが、現mediaはlibvips CLI、mail機能は未実装。Frontはpackage管理外のCDN JSも使う。CSS/themeとFront Businessの複製も増えている。
- 影響: attack surface、update作業、license/SBOM、build時間を増やす。依存があることが機能実装済みという誤認にもなる。
- 改善: runtime/import使用を機械的に検査し、未使用packageを削除するか採用理由と導入期限を記録する。browser assetも同じlock/SBOMへ統合する。
- 完了条件: manifestの各dependencyに実使用箇所または明示的な保留理由がある。

### P2-17 configuration parsingが各containerへ複製され、検証が遅い

- 根拠: env string/csv/bool parsingがShared、Front、CMS、Front Businessに重複し、unknown値の多くはdefaultへ落ちる。`.env.example` のDB portは3306だがPostgreSQL標準は5432である。
- 影響: 同じkeyでもfallbackやserver/getenv参照順が違い、typoが起動後の挙動差になる。
- 改善: Kernel側にtyped immutable configとstartup validationを置き、Appへ必要部分だけ渡す。secretは値をlogせず、missing key名だけをまとめて報告する。
- 完了条件: invalid enum/URL/port/bool/CORS組合せが起動時に一括失敗し、`.env.example` からfresh起動できる。

### P2-18 worker singletonのscopeがservice IDだけでglobalである

- 根拠: `Container::$workerInstances` はstaticでIDだけをkeyにし、test/reset APIを持たない。異なるApplication/connection configを同一processで作ると最初のinstanceを共有する。
- 影響: 現在の単一App workerでは成立するが、multi-tenant/test/hot reload/複数Kernelで誤接続やtest汚染を起こし得る。
- 改善: worker scope ID/Application IDをkeyへ含めるか、worker container自体を明示lifecycle objectにする。request leak testとshutdown/reset hookを持つ。
- 完了条件: 異なるconfigの二Applicationを同一processで連続実行してもPDO/configが混ざらない。

## 9. P3: 局所的だが早めに直せる改善点

1. README、seed、`.env.example` の初期email/DB portを一致させる。seedを繰り返すならidempotentにするか「空DBで一度だけ」と明記する。
2. `SELECT *` を必要列へ限定し、schema追加でread modelの意味が暗黙に変わらないようにする。
3. Tiptap URLの `str_starts_with('/', ...)` は `//host/path` も許す。site-relativeは単一 `/` だけ、外部URLは許可scheme/host policyを分ける。
4. upload/log/cache directoryの0775を固定せず、container user/umask/read-only mountを含むleast privilegeで決める。
5. capture scriptの `/mnt/data/...` 絶対pathをrepository-relative/CLI引数にし、再生成環境とbrowser/font versionをlockする。
6. Front/Front Business Presentation READMEの根拠のない点数表現を、変更scenarioと測定結果へ置き換える。
7. `APP_DEBUG`、`MAIL_DSN`、`CMS_DEFAULT_*` のように未使用のenvは実装するまで削除し、「設定したのに効かない」をなくす。
8. Auditのrequest IDは外部headerをそのまま信頼せず、長さ/文字種を制限し、内部生成IDと外部correlation IDを分ける。

## 10. 品質属性シナリオとして固定すべき受入条件

抽象的に「セキュア」「変更容易」と書く代わりに、少なくとも次をtestable scenarioにする。

| 刺激 | 環境 | 期待応答 | 測定例 |
|---|---|---|---|
| 未処理例外 | production HTTP | 固定500、correlated log/metric、秘密非表示 | log到達100%、request ID一致 |
| Audit保存先disk full | 記事save直後 | 業務結果が曖昧にならず、欠落alert | HTTP/DB結果が仕様どおり |
| 同じ記事を二者が更新 | 通常運用 | 一方成功、一方conflict | silent overwrite 0 |
| 二adminを同時降格 | 通常運用 | 少なくとも一方拒否 | admin count >= 1 |
| 512MiB/偽装/巨大pixel upload | 攻撃・誤操作 | 早期拒否、worker/disk保護 | memory/CPU/disk上限内 |
| Host/forwarded header偽装 | internet直結/proxy経由 | 未許可Host拒否、scheme正規化 | canonical/cookie誤り0 |
| 匿名記事GET | 通常 | sessionなし、cache可能 | Set-Cookieなし、cache hit可 |
| 問い合わせburst | bot traffic | rate limit、正規利用は継続 | DB増加/分、false positive率 |
| DB+media restore | 空環境 | 記事と画像が同じ時点へ復元 | RPO/RTO内、missing media 0 |
| CDN停止 | 通常閲覧/CMS編集 | self-hostなら無影響、または明示degrade | core journey成功 |
| schemaの必須列破損 | read時 | mapping失敗を検知し相関記録 | silent default 0 |
| 10万記事検索 | 想定最大 | bounded response/pagination | p95、query plan、memory上限 |

## 11. 推奨する実施順

### Work Unit A: production safety baseline

1. production exception handler、request ID、application error log、最小HTTP metric。
2. typed config validation、必須secret、seed guard、Host/proxy/TLS boundary。
3. CSRF常時有効化、public session lazy化、実response CSPの単一owner化。
4. 問い合わせrate limit/retention、upload limit/staging/cleanup。

### Work Unit B: data and operation semantics

1. FK/CHECKとdelete policy、settings singleton、last-admin transaction。
2. optimistic concurrency、Page restore結果、media reconciliation。
3. Event dispatcherの名前/失敗契約、Audit/Security Logの成否方針。

### Work Unit C: recoverability and observability

1. DB+media backup/restore drill、RPO/RTO、deploy/rollback runbook。
2. log rotation/retention/integrity/exportとAudit検索storage。
3. SLI/SLO、golden signals、synthetic smoke、容量監視。

### Work Unit D: simplify and align

1. capabilityの縦slice（noindex、tags、taxonomy SEO、OGP等）を優先度順に完成または削除。
2. architecture ruleをinvariant/boundary/pattern/styleへ分割し、hollow VO/tripletを実証的に簡素化。
3. Front Businessの製品境界、role matrix、docs canonical ownerを決定。
4. PostgreSQL integration、browser E2E、accessibility、concurrency、recovery testをriskに対応付ける。

## 12. この報告書の扱い

このファイルを第二の `plan.md` やarchitecture rulesにしない。各項目を採用・保留・却下へトリアージし、次のように移す。

- 採用した直近作業: `docs/plan.md`
- 長期に守る判断とtrade-off: `docs/decisions.md`
- 現行機能の完成条件: `docs/feature_inventory.md` または対応するrequirements
- 永続的な境界規約: `docs/webmedia_architecture_rules.md`
- production手順: 新しい単一runbook、または既存の運用文書

全項目の移管・解決後、このpoint-in-timeレビューは削除してよい。Git履歴が当時の監査記録になる。
