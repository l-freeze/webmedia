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
- Frameworkは`packages/framework`を自己完結したpackage rootとし、webmedia rootはComposer path repositoryから`pera/framework`を利用する。root PSR-4でFramework namespaceを重複autoloadしない。
- Composer installer pluginは持たず、Framework install時にKernelやSharedを暗黙生成しない。
- Dotenvによる任意の`.env`読み込みは標準Kernel scaffoldに含める。環境初期化はapplication compositionより前に行うKernelの責務とし、Sharedには置かない。`vlucas/phpdotenv`はFramework packageではなく生成物を使うconsumer rootが所有する。
- 正式に利用可能とするscaffoldはKernel、Shared、Appの`bare` / `minimal` / `todo` / `layered`全種とする。将来予約の`fullpack`は実装されるまで対象外とする。

### 配布とscaffold

1. 新規project skeletonのroot `composer.json`は`pera/framework`と標準starter依存をrequireし、Kernel / Shared / public entrypoint / 最小appを含む。`public/index.php`、consumer側PSR-4、最小app登録、必要dependencyを含め、fresh projectが手編集なしで最初の200 responseを返す完成条件を決める。Frameworkを使うだけのminimal構成ではSharedやDBを必須にしない。
2. package名は`pera/framework`とする。public API / pre-1.0互換性、changelog、security policy、archive除外方針はpackage内文書を正とする。公開前にlicense、公開repository URL、security reporting URL、初回tagを確定する。

### 後で行うFramework / Kernel改善

- `IApplication::handleGlobals(): void` とRequest生成・Response送信のlifecycle境界を再検討する。
- production exception handler、request ID、通常loggerの標準合成方法を決める。
- `Request::isHttps()` のtrusted proxy判定を暗黙header trustから分離する。
- 未知のruntime指定や利用不能なFrankenPHP指定をsilent fallbackさせるか、起動失敗にするか決める。
- Routerのmetadata取得とdispatchで同じroute matchを二度行う構造を整理する。

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
