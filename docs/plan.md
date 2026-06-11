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

### 次タスク: Architecture Test の追加

次に作業を再開する場合は、`docs/refactoring.md` の Architecture Test 候補をもとに、アプリケーション構造の依存方向を検査するテストを追加する。

実装候補:

- `apps/front` から `App\Cms` へ依存していないことを検査する。
- `apps/cms` から `App\Front` へ依存していないことを検査する。
- `Application` が `Infrastructure` に依存していないことを検査する。
- `Domain` が `Application` / `Infrastructure` に依存していないことを検査する。
- Controller の不要な `as` alias 検査と UseCase `Input` の named arguments 利用検査は、依存方向テストと別テストに分けるか、同一ファイル内でも別 test method として扱う。

このタスクは、今回完了した scaffold template 整備とは別作業として扱う。

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
