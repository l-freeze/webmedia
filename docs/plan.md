# WEBメディア実装計画

## 前提

- 作業前に `docs/current_status.md` を確認する。
- 作業完了時は `docs/current_status.md` を更新する。
- PHP / Composer はホストではなく Docker Compose 経由で実行する。
- `apps/cms` と `apps/front` の業務ロジックは分離する。
- Framework は HTTP / DI / Routing / View / Security などの薄い基盤に留める。
- 詳細な構造規約は `docs/webmedia_architecture_rules.md` を正とする。

## 現在の優先順

1. OGP / Twitter Card、RSS / Atom の仕様を決める。場当たり的に仮実装しない。
2. CMS / Front の PC/SP 表示を、日本語フォントがある環境または Playwright 環境で再確認する。
3. `docs/application_ports.md` と現行実装の整合性を確認する。
   - Application が外側の能力へ依存している箇所と、現行の Port 配置を洗い出す。
   - 資料と実装のどちらを直すべきか、UseCase の要求、依存方向、横断基盤との違いを見て個別に判断する。
   - 判断前に Port を機械的に追加・削除しない。

## 後で忘れず判断すること

- 問い合わせフォームの最低限の rate limit / spam 対策 / プライバシー同意文言。積極運用しない前提なので直近優先にはしないが、公開フォームとして外部入力口になるため公開前には判断する。

## Framework Composer package化の残作業

### 決定済みの前提

- Frameworkのpublic namespaceは `Pera\Framework` とする。
- Frameworkを `composer require` しただけではKernelを自動生成しない。Kernelを使うかどうかは利用者が決める。
- scaffoldで生成した `App\Kernel` は全て利用プロジェクトの所有物とし、Framework updateで上書き、差分適用、自動upgradeをしない。
- PSRに関する採用範囲と独自境界は各Framework READMEとtechnical stackに既に記載しているため、要件が変わらない限り再検討タスクにしない。
- 新規projectの標準導入はproject skeleton、既存projectへの追加は `composer require` 後の明示的なvendor binary実行を基本とする。

### 依存分離

1. Framework配布物専用の `composer.json` を用意し、webmedia root packageから独立して `composer validate --strict` できるようにする。
2. Framework packageの `require` は、Framework production codeが直接使うPHP version、FastRoute、PSR Container、PSR Logと、vendor binaryが依存するComposer runtime APIだけから始める。実使用scanと隔離install testで最終確定する。
3. Dotenv、Latte、PDO driver、Phinxはproject skeletonまたは利用側root packageへ置く。CommonMark、HTML Purifier、Mailer、画像処理は、それを使うapp/project側に残す。
4. standard Shared scaffoldが生成する具象実装について、必要packageが未導入なら生成前に不足一覧とroot projectへ追加するコマンドを表示する。Framework側へ暗黙に依存を戻さない。
5. 新規project skeletonのroot `composer.json` は `pera/framework` と標準starter依存をrequireし、Kernel / Shared / public entrypoint / 最小appを含む。Frameworkを使うだけのminimal構成ではSharedやDBを必須にしない。
6. monorepoで開発を続ける間はComposer path repositoryでFramework packageをroot applicationから利用し、monorepo rootのautoloadが欠落を隠さない状態にする。

### 配布とscaffold

1. `bin/scaffold-*` 内の巨大なheredocをversion管理されたtemplate fileへ分離し、現行Kernelと生成Kernelのdriftを検出する。
2. Kernel scaffold、Shared scaffold、App scaffoldを、空のconsumer projectとpackaged artifactから実行するE2E testを追加する。生成後のautoload、PHP構文、PHPStan、最小HTTP応答まで確認する。
3. `public/index.php`、consumer側PSR-4、最小app登録、必要dependencyを含め、fresh projectが手編集なしで最初の200 responseを返す完成条件を決める。
4. 部分生成、既存file、書込み失敗時のatomicityと再実行方針を決める。既存利用者fileは常に非上書きを既定とする。
5. Composer plugin prototypeは標準導入経路から外す。自動生成が必要になった場合だけ、独立したplugin packageとしてmanifest、許可、failure、uninstallを再設計する。
6. package名、license、public API / semantic versioning、changelog、security policy、配布除外fileを確定する。

### 後で行うFramework / Kernel改善

- `IApplication::handleGlobals(): void` とRequest生成・Response送信のlifecycle境界を再検討する。
- Kernelの全request Session開始をやめ、sessionを必要なapp/routeだけに限定する。
- production exception handler、request ID、通常loggerの標準合成方法を決める。
- `Request::isHttps()` のtrusted proxy判定を暗黙header trustから分離する。
- 未知のruntime指定や利用不能なFrankenPHP指定をsilent fallbackさせるか、起動失敗にするか決める。
- Routerのmetadata取得とdispatchで同じroute matchを二度行う構造を整理する。
- 現行Kernelとscaffold Kernelの差分を解消する。ただし生成済みKernelの自動更新機構は作らない。

## 完了済みの大枠

- Docker / Composer / FrankenPHP 開発基盤。
- 軽量 Framework 基盤。
- Front / CMS / Shared のアプリケーション分離。
- Front 主要表示画面。
- CMS 主要管理画面。
- CMS 認証・認可の初期実装。
- Audit / SecurityLog の初期実装。
- Architecture Test の初期実装。
- scaffold-app template 整備。
- 主要 Front / CMS 画面の HTTP 確認。
- 記事作成・編集・プレビュー・履歴復元、カテゴリ、タグ、問い合わせ送信の HTTP 経由確認。
- Audit 管理画面。
- Audit 管理画面のページング、日時ソート、日時/結果/操作/対象/ID/ユーザーの絞り込み。
- 管理ログインの試行回数制限。
- CMS 状態変更 Audit middleware と、記事/固定ページ/分類/問い合わせ/管理者/設定/メディア削除の業務要約。
- 問い合わせ一覧の 100 件ページング、問い合わせ詳細画面、問い合わせ入力の文字数上限。
- 固定ページ編集画面からの履歴復元。
- 問い合わせ詳細画面からの状態更新と管理メモ保存。
- libvips CLI による画像サムネイル / 最適化 WebP 生成。

## 公開前に必ず確認すること

- `APP_DEBUG=false`
- HTTPS
- seed の初期管理者アカウントを本番に残さない
- 管理画面の追加防御
- DB と uploads のバックアップ / 復元手順
- `robots.txt`
- `sitemap.xml`
- OGP / Twitter Card
- RSS / Atom
- 問い合わせフォームの spam 対策と個人情報取得説明
- 本番デプロイ、ロールバック、障害時確認手順
