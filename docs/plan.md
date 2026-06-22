# WEBメディア実装計画

## 前提

- 本ディレクトリをプロジェクトルートとする。
- 作業を開始する前に必ず `docs/current_status.md` を確認し、現在の完了地点・再開地点・既知の課題を把握する。
- 作業を中断または完了するときは、必ず `docs/current_status.md` を更新し、次に再開すべき地点を明記する。
- ホスト側に PHP / Composer をインストールする前提にはしない。PHP / Composer コマンドは Docker コンテナ内で実行する。
- Laravel / WordPress は使わず、自前の軽量 PHP アプリケーション基盤を作る。
- Composer、Docker、Docker Compose を必須とする。
- PHP は技術資料の指定に合わせて `8.5` 系を採用し、開発環境では `dunglas/frankenphp:php8.5` を使用する。
- `apps/cms` と `apps/front` の業務ロジックは完全に分離し、曖昧な shared/common/core/utils/helpers 層は作らない。
- DI、HTTP、Routing、View、Security などフレームワークレベルの機能は、Laravel のコア機能と同じ位置付けで `App\Framework` として共通利用する。
- フロントは SSR を基本とし、必要な UI 状態のみ Stimulus、部分更新余地に htmx を導入する。
- 初回実装は、小規模メディアとして動作確認できる MVP を完成範囲にする。

## 完成範囲

1. Composer / Docker / Docker Compose による開発基盤
2. FastRoute、Latte、PDO、Phinx、Dotenv、CommonMark、HTMLPurifier、Symfony Mailer、Intervention Image の導入
3. PSR-11 風 DI コンテナ、HTTP Request / Response、ルーター、テンプレートレンダラー、CSRF、セッション、DB 接続基盤
4. `apps/front` と `apps/cms` のアプリケーション分離、および `Application/<対象>/<ユースケース>` 構造
5. 既存モックを参考にしたフロント/CMS の HTML/CSS/JS
6. フロント主要画面
   - トップ
   - 記事詳細
   - カテゴリ一覧/詳細
   - タグ一覧/詳細
   - 検索結果/検索結果なし
   - 固定ページ
   - 問い合わせ入力/エラー/完了
   - 404
   - ローディング/記事なし状態
   - スマホメニュー
7. CMS 主要画面
   - ログイン/ログアウト
   - ダッシュボード
   - 記事一覧
   - 記事作成/編集
   - 公開/非公開/公開予約/ゴミ箱
   - プレビュー
   - 更新履歴/簡易差分/復元
   - 固定ページ管理
   - カテゴリ/タグ管理
   - メディア管理
   - 問い合わせ管理
   - サイト設定
   - ゴミ箱
8. Phinx マイグレーションと最小シード
9. PHPUnit による基盤、ドメイン、主要ユースケース、HTTP のテスト

## 作業順序

### 次タスク: CMS 認証・認可基盤の段階実装

次に作業を再開する場合は、CMS の `Guard` / `Role` / `Permission` / `Policy` / `Audit` / `Log` / `Group` を一度に全部実装せず、認証・認可の強制境界から段階的に追加する。

Policy 実装の詳細計画は `docs/policy_implementation_plan.md` を参照する。Guard 初期実装は完了済みのため、次は Permission / Role mapping、`AuthorizationService`、route-level permission middleware を追加し、その後に最初の具体 Policy として `ArticlePolicy` を検討する。

基本方針:

- Guard は Session の延長として開始してよいが、middleware と同一責務にしない。
- Middleware は HTTP pipeline の入口制御を担当する。
- Guard は「現在の request が CMS ユーザーとして認証済みか」「現在ユーザーは誰か」を解決する認証境界にする。
- Permission は操作名、Role は Permission の集合、Policy は対象リソースや状態を含む判定として分ける。
- UI の表示制御だけでは認可にならない。強制チェックは route middleware と UseCase / Policy の二段階で行う。
- Role / Permission ルールが安定するまでは DB 化せず、コード上の mapping から始める。
- Group はチーム、部署、複数サイトなどの単位が必要になるまで実装しない。
- Audit と Log は目的が違うため混ぜない。Audit は業務証跡、Log は障害調査・アクセス・セキュリティイベントに分ける。

実装順:

1. `CurrentCmsUser` と `CmsGuard` を追加する。
   - `CmsGuard` は `Session` から `cms.user_id` を読み、必要なユーザー情報を解決して `CurrentCmsUser` を返す。
   - 未ログイン時は `null` または明示的な未認証結果を返し、Permission 判定はしない。
   - 既存の `RequireCmsSessionMiddleware` は、直接 `Session` を解釈する実装から `CmsGuard` を呼ぶ実装へ寄せる。
   - この段階では「ログイン済みか」の強制だけを維持し、権限差はまだ付けない。
2. CMS auth middleware を整理する。
   - 既存の `RequireCmsSessionMiddleware` は当面継続利用する。Guard へ委譲済みで責務は入口制御に寄っているため、改名は route-level permission middleware 追加後に必要性が出た場合だけ検討する。
   - いずれの場合も middleware は Guard を使って認証済みか確認し、未認証なら `/admin/login` へ redirect する。
   - Controller ごとの `Session` 直接参照によるログイン必須判定を増やさない。
3. Permission 名と Role mapping をコード上に定義する。
   - Permission は `article.create`、`article.update`、`article.delete`、`article.publish`、`page.update`、`media.upload`、`media.delete`、`setting.update`、`taxonomy.manage`、`inquiry.read` のような操作名にする。
   - Role はまず `admin`、`editor`、`writer`、`viewer` 程度の最小構成にする。
   - Role / Permission 管理画面や DB テーブルは、この段階では作らない。
4. `AuthorizationService` を追加する。
   - `can(CurrentCmsUser $user, string $permission): bool` と `authorize(...)` を用意する。
   - middleware、Controller、UseCase から同じ入口で使えるようにする。
   - 権限拒否時の例外または結果型を決め、HTTP 403 へ変換できるようにする。
5. route-level permission check を追加する。
   - ルート定義または middleware 設定で `cms.can:article.update` 相当を表現できるようにする。
   - まずは書き込み系や破壊的操作から適用する。
   - route-level check は「このルートに入ってよいか」の粗い判定に限定し、対象記事の状態などは見ない。
6. Latte 用の `can()` helper を追加する。
   - 管理画面のボタン、リンク、操作導線の表示制御に使う。
   - これは補助であり、強制力のある認可は middleware / UseCase / Policy で行う。
7. 重要な UseCase に認可チェックを追加する。
   - 記事保存、公開、削除、復元、固定ページ保存、メディア削除、設定更新、分類更新、問い合わせ閲覧を優先する。
   - 直接 POST や将来の CLI / API から実行されても拒否できるよう、UseCase 側にも最終チェックを置く。
8. 必要になったリソースから Policy を追加する。
   - 最初から全リソースに空の Policy を作らない。
   - 「自分の記事だけ編集できる」「公開済み記事は editor 以上のみ」「削除は admin のみ」など、対象リソースや状態を見る条件が出た時点で追加する。
9. 認可拒否と重要操作の Audit を追加する。
   - 既存の `CmsAuditMiddleware` は HTTP envelope の証跡として維持する。
   - `authorization.denied`、`article.updated`、`media.deleted` などの業務イベントは UseCase / Controller から Business Audit として明示記録する。
   - 現在の標準実装は `JsonFileAuditLogger` で `storage/logs/audit.log` へ保存する。DB 保存が必要な段階で `AuditLoggerInterface` を `PdoAuditLogger` などへ差し替える。
10. Security / auth log を必要に応じて追加する。
    - ログイン成功、ログイン失敗、ロックアウト、CSRF 失敗、認可拒否などは audit log と分けて扱う。
    - HTTP access log も別概念として扱い、全 request を Audit に入れない。
11. Group は最後に検討する。
    - 複数チーム、複数部署、複数サイト、所有グループ単位の閲覧・編集制御が必要になった場合だけ実装する。
    - Group を追加する場合は、Role / Permission / Policy の上に条件として載せ、先に Group テーブルだけを作らない。

実装時の注意:

- `src/Framework` に CMS 固有の Guard / Role / Permission / Policy を置かない。
- CMS 固有の middleware は、現行方針どおり `apps/cms/src/Presentation/Http/Middleware` を基本位置にする。`apps/cms/src/Infrastructure/Http` は、proxy header、upload limit、外部基盤都合の request/response 制約など Infrastructure と呼べる責務が出た時点で追加する。
- Guard / CurrentUser / AuthorizationService / Policy の置き場所は、CMS に閉じるものは `apps/cms` 配下、Front / CMS 共通の基盤になったものだけ `apps/shared` 配下を検討する。
- route middleware metadata を追加する場合は、既存の `config/middleware.php` とルーターの責務を壊さないよう、先に最小の設計を切る。
- route metadata attribute、HTTP request metadata 抽出、route match の重複など、middleware 境界の保留論点は `docs/middleware_boundary_review.md` を参照する。
- テストは `CmsGuard`、auth middleware、`AuthorizationService`、permission middleware、UseCase-level authorization の順で追加する。

### 後続候補: Architecture Test の追加

`docs/refactoring.md` の Architecture Test 候補をもとに、アプリケーション構造の依存方向を検査するテストを追加する。

実装候補:

- `apps/front` から `App\Cms` へ依存していないことを検査する。
- `apps/cms` から `App\Front` へ依存していないことを検査する。
- `Application` が `Infrastructure` に依存していないことを検査する。
- `Domain` が `Application` / `Infrastructure` に依存していないことを検査する。
- Controller の不要な `as` alias 検査と UseCase `Input` の named arguments 利用検査は、依存方向テストと別テストに分けるか、同一ファイル内でも別 test method として扱う。

### 完了: Audit middleware 設計・初期実装

監査ログミドルウェアの設計・実装手順は `docs/audit_middleware.md` に整理済み。初期実装として `CmsAuditMiddleware` と単体テスト、CMS middleware config への登録を追加済み。

方針:

- `PlaceholderAuditLogger` は scaffold やテストで未設定検知したい場合の実装として維持する。
- 初期実装で追加する middleware は `CmsAuditMiddleware` ひとつにする。
- HTTP access log、auth/security log、audit log は目的が異なるため概念として分ける。
- GET と CMS ログインは初期 Audit 対象外にする。
- CMS の状態変更 request は allowlist ではなく denylist 方式で広く監査対象にする。
- CMS の状態変更 request を middleware で記録し、before / after などの業務詳細が必要な操作は UseCase / Controller から Business Audit として明示的に記録する。
- 標準 shared container は `AuditLoggerInterface` に `JsonFileAuditLogger` を登録し、`storage/logs/audit.log` へ保存する。DB 保存が必要なアプリケーションだけ `PdoAuditLogger` などへ bind を差し替える。
- 現在の CMS middleware config では `RequireCmsSessionMiddleware` の後に `CmsAuditMiddleware` を登録済み。

### 完了: scaffold-app template 整備

`bin/scaffold-app` の template 構成を整理済み。

実装対象:

- `--template=bare`
  - 現在の `minimal` と同じ config only template にする。
  - 生成物は `config/routes.php`、`config/container.php`、`config/middleware.php`、`src/.gitkeep`。
  - route、Controller、view template、TemplateRenderer binding は生成しない。
- `--template=minimal`
  - 余計な層を作らず、1つの Controller ソースに処理を寄せた「動く最小アプリ」にする。
  - Controller は同一ソース内で DB 接続の取得、必要な最小データ処理、TemplateRenderer 経由の HTML 出力までを行う。
  - 一枚絵の HTML を返す Latte template を生成する。
  - `config/routes.php` には `GET <path-prefix>` の route を追加する。
  - `config/container.php` には `TemplateRenderer::class` の binding を追加する。
  - DB は Framework / Kernel 側の既存 `PDO::class` binding を利用し、scaffold 側で独自の sqlite binding は生成しない。
- `--template=layered`
  - 現在ある layered starter を維持する。
  - Application / Domain / Infrastructure の分割例を含む template として扱う。
- `--template=fullpack`
  - 今後、policy、guard、audit、permission、role などが充実してきた段階で追加する全部載せ template の予約名とする。
  - 今回の実装では有効な template として追加しない。README 上の将来候補として触れるに留める。

実装時の注意:

- `bin/scaffold-app` の usage / validation / 出力メッセージを更新する。
- `bin/README.md`、必要なら `apps/kernel/README.md` と `src/Framework/README.md` を更新する。
- 既存の `apps/minimal` は試作用の参考実装であり、scaffold 生成物ではない。
- `apps/minimal/config/container.php` の sqlite `PDO::class` binding は、新しい `minimal` scaffold へそのまま入れない。
- `GET <path-prefix>` の route は、`path-prefix` が `/` の場合は `/`、それ以外は末尾 slash を除いた prefix にする。
- 生成後メッセージでは、`bare` は config only、`minimal` は single-controller HTML starter、`layered` は Application / Domain / Infrastructure starter であることを明示する。
- `fullpack` は今回の生成対象ではないため、usage / validation には追加しない。README に将来候補として記載するだけにする。
- 互換性上、これまで `--template=minimal` で config only を期待していた自動化は `--template=bare` へ変更する必要がある。README に移行先を明記する。
- `tests/Scaffold/ScaffoldAppTest.php` に `bare` と新しい `minimal` の生成物テストを追加し、既存 `todo` / `layered` の扱いを壊さない。
- PHP / Composer / PHPUnit はホストではなく Docker Compose 経由で実行する。

このタスクでは Architecture Test は扱わない。Architecture Test はアプリケーション構造の検査であり、今回の scaffold template 整備とは作業単位を分ける。

### 1. 計画の確定

- `docs/README.md` と関連資料から実装方針を整理する。
- 本ファイル `docs/plan.md` を作成する。
- コミット: `計画書を追加`

### 2. Composer と Docker 開発基盤

- `composer.json` を作成し、PSR-4 autoload を設定する。
- 必要ライブラリと開発ライブラリを導入する。
- `docker-compose.yml`、`Dockerfile`、`Caddyfile`、`.env.example`、`phinx.php` を作成する。
- `Makefile` を用意し、起動、停止、依存導入、マイグレーション、テストを実行しやすくする。
- コミット: `ComposerとDocker開発基盤を追加`

### 3. 軽量フレームワーク基盤

- DI コンテナ、HTTP、ルーティング、ビュー、セッション、CSRF、DB 接続を `src/Framework` に実装する。
- CMS と Front には業務機能、設定、ルート、テンプレートを分離して配置する。
- `public/` 配下に Front / CMS のエントリポイントを置く。
- コミット: `軽量PHP基盤を追加`

### 4. アーキテクチャに沿ったディレクトリとDB

- `apps/front/src` と `apps/cms/src` に `Application/<対象>/<ユースケース>/{Input,Interactor,Output}`、`Domain/Repository`、`Infrastructure` の構造を作成する。
- `database/migrations` に主要テーブルを作成するマイグレーションを追加する。
- `database/seeds` にローカル確認用データを追加する。
- コミット: `アプリ構造とデータベース定義を追加`

### 5. HTML/CSS/JS デザイン実装

- 既存デザインモックを参考に、SSR テンプレートとして Front / CMS の画面を作成する。
- Tailwind v4 の利用方針に沿いつつ、初回はビルド依存を軽くするため CSS 変数とユーティリティ風クラスを `public/assets/css` に配置する。
- htmx、Stimulus、EasyMDE は CDN で読み込む。
- コミット: `フロントとCMSの画面デザインを追加`

### 6. プログラム開発

- Front の読み取り UseCase、Application Query interface、Controller を実装する。
- CMS の認証、記事、固定ページ、分類、メディア、問い合わせ、設定、ゴミ箱の UseCase、Application Query interface、Domain Repository interface、Controller を実装する。
- Markdown 変換、HTML サニタイズ、CSRF、セッション認証、バリデーションを適用する。
- コミット: `主要機能を実装`

### 7. テストケース作成

- PHPUnit 設定を追加する。
- DI、ルーティング、CSRF、記事ステータス、Markdown サニタイズ、Front 表示、CMS 記事保存のテストを追加する。
- Docker Compose 上でテストを実行する。
- コミット: `テストケースを追加`

### 8. 最終確認

- Docker コンテナ内で `composer install`、`composer test`、マイグレーション、Docker Compose 起動確認を行う。
- `git status` で未コミットの作業を確認する。
- 必要なら README に起動方法を追記する。
- コミット: 必要に応じて `開発手順を追加`

## 公開前の評価

現状の `apps/cms` / `apps/front` は個人運用のブログシステムとして十分な土台を持つが、一般公開する場合はコード本体だけでなく、本番運用、SEO、スパム対策、法務ページを公開前に整える。

### 良い点

- `apps/cms` と `apps/front` が分離されており、管理機能と公開機能の責務が読みやすい。
- `Application` / `Domain` / `Infrastructure` の分割が揃っており、一人運用でも変更箇所を追いやすい。
- 記事、固定ページ、カテゴリ、タグ、メディア、問い合わせ、ゴミ箱、履歴、復元、公開期間を持っており、個人ブログとしては機能の土台が厚い。
- 管理画面の主要 POST に CSRF が入っている。
- ログイン成功時に session regenerate しており、基本的なセッション固定攻撃対策がある。
- Markdown 変換と HTML サニタイズを使う前提になっており、記事本文の安全性を意識している。
- メディアアップロードは MIME type、サイズ、ランダムファイル名を扱っており、最低限以上の防御がある。
- 公開予約、公開終了、記事履歴、固定ページ履歴があり、運用中の事故を戻しやすい。
- 固定ページを持てるため、運営者情報、プライバシーポリシー、免責事項などを CMS から管理できる。

### 悪い点

- 管理ログインに試行回数制限、IP 制限、2FA などの追加防御がない。
- 問い合わせフォームに rate limit、honeypot、CAPTCHA 相当のスパム対策がない。
- 問い合わせは保存されるが、メール通知や確認運用が未整備。
- `robots.txt`、`sitemap.xml`、OGP、RSS / Atom が未整備。
- seed の初期管理者アカウントを本番に残すと危険。
- 本番デプロイ、バックアップ、復元、障害時確認の手順がドキュメント化されていない。
- 外部 CDN への依存があり、本番では可用性と改ざんリスクを考える必要がある。
- 初期データのプライバシーポリシー本文は公開用としては薄い。

### 公開に向けての準備

#### 公開前に必ず対応すること

- 本番環境では `APP_DEBUG=false` にする。
- HTTPS を有効にする。
- seed の `admin@example.com` / `password` を本番に残さない。
- 本番用管理者アカウントを作り、強いパスワードを設定する。
- 管理画面 `/admin` に IP 制限、Basic 認証、または同等の追加防御を入れる。
- 管理ログインに試行回数制限を入れる。
- DB と `public/uploads` の定期バックアップを用意する。
- バックアップから復元できることを本番公開前に一度確認する。
- `robots.txt` を追加する。
- `sitemap.xml` を追加する。
- OGP / Twitter Card の meta tag を出力する。
- 404 ページ、記事なし、検索結果なしの表示を本番データで確認する。
- 外部 CDN 依存の JS / CSS は、可能ならローカル配信に切り替える。
- 本番デプロイ手順、ロールバック手順、障害時の確認手順を `docs/` に残す。

#### 対応済み

- 記事詳細、カテゴリ、タグ、固定ページに canonical URL を出力する。

#### 問い合わせフォームを残す場合に必須の対応

問い合わせフォームは、名前、メールアドレス、件名、本文を取得するため、個人情報の取得を伴う。公開する場合は以下を対応する。

- プライバシーポリシーを公開する。
- 問い合わせフォームの近くに「プライバシーポリシーに同意して送信する」旨を表示する。
- 送信内容の利用目的を明記する。
- 取得する個人情報の項目を明記する。
- 保存期間、または不要になった時点で削除する方針を明記する。
- 第三者提供を行わないことを明記する。行う可能性がある場合は条件を明記する。
- 開示、訂正、削除、利用停止の問い合わせ先を明記する。
- 問い合わせフォームに rate limit、honeypot、CAPTCHA 相当のスパム対策を入れる。
- 問い合わせ送信時に管理者へ通知する仕組みを入れるか、問い合わせ一覧を定期確認する運用を決める。
- 問い合わせ本文に秘密情報を入力しないよう注意書きを入れる。

問い合わせフォームを完全に除去し、メールアドレスも掲載せず、コメント欄や会員登録など他の個人情報取得機能も置かない場合、個人情報取得に直接対応するプライバシーポリシーの必要性は下がる。ただし、アクセス解析、広告、アフィリエイト、外部埋め込み、Cookie を使う場合は、問い合わせフォームの有無に関係なくプライバシーポリシーまたはそれに相当する説明ページを用意する。

#### 法務ページとして用意するもの

- 運営者情報
  - サイト名
  - 運営者名またはハンドルネーム
  - サイトの目的
  - 連絡方法
  - 問い合わせフォームを残す場合は問い合わせフォームへのリンク
- プライバシーポリシー
  - 取得する情報
  - 利用目的
  - 保存期間
  - 第三者提供の有無
  - 外部サービス利用の有無
  - Cookie / アクセス解析 / 広告の利用有無
  - 開示、訂正、削除、利用停止の連絡先
  - 制定日、改定日
- 免責事項
  - 掲載情報の正確性を保証しないこと
  - 情報利用による損害について責任を負わないこと
  - リンク先外部サイトの内容に責任を負わないこと
  - 技術記事の場合、環境差やバージョン差があること
- 著作権・引用ポリシー
  - 記事、画像、コードの権利帰属
  - 無断転載を禁止する範囲
  - 引用時の条件
  - コード断片の利用条件
- 広告・アフィリエイト表記
  - 広告を掲載する場合に用意する。
  - アフィリエイトリンクを使う場合に用意する。
  - PR 記事、レビュー提供、成果報酬リンクの扱いを明記する。
  - 広告やアフィリエイトを一切使わない段階では必須ではない。

#### ブログとして追加したい標準機能

- RSS / Atom フィード
- サイトマップページ
- 記事一覧の年月別アーカイブ
- 記事下の関連記事導線
- 人気記事、またはおすすめ記事枠
- パンくずリスト
- 記事の更新日表示
- 画像 alt の編集と表示確認
- リダイレクト管理 UI
- 下書きプレビュー URL の保護強化
- 管理画面操作ログの実保存

#### 推奨ホスティング方針

このシステムは PHP 8.5、PostgreSQL、FrankenPHP / Caddy、Docker Compose を前提にしやすいため、一般的なレンタルサーバーより VPS が向いている。個人サービスとしては 2GB メモリの VPS を第一候補にする。

- 第一候補: さくらのVPS 2G
- 第二候補: ConoHa VPS 2GB
- 低コスト優先: 1GB VPS でも小規模なら動く可能性はあるが、PostgreSQL、画像処理、動画アップロードを考えると 2GB を推奨する。
- 避けたい構成: PHP バージョン、PostgreSQL、Docker、常駐プロセス、アップロードサイズに制限が強いレンタルサーバー。

# 備考

レイアウトキャッシュが複雑化した場合の改善対応案は `docs/layout_global_provider_refactoring.md` を参照する。現時点では `LayoutGlobalProvider` 内で `globals()` と `loadGlobals()` を分けるに留め、provider interface / cache decorator への分離は複雑化の兆候が出た時点で検討する。
