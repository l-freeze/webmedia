# 現在の作業状況

## 更新日時

- 2026-05-23

## 運用ルール

- 作業開始前にこの `docs/current_status.md` を確認する。
- 作業中断時または完了時に、このファイルを更新する。
- 次回再開地点、未完了タスク、検証結果を明記する。
- PHP / Composer はホストではなく Docker コンテナ内で実行する。

## 完了済み

1. `docs/plan.md` 作成
   - コミット: `e9aaffe 計画書を追加`
2. Composer / Docker Compose / FrankenPHP / MariaDB 開発基盤
   - コミット: `3ac1671 ComposerとDocker開発基盤を追加`
3. 共通軽量フレームワーク基盤
   - `src/Framework` に DI、HTTP、Routing、View、Security、DB、Markdown を配置
   - Framework は `cms/front` の種別に関心を持たず、DI と各アプリ設定で差し替える方針
   - コミット: `a483a8d 軽量PHP基盤を追加`
4. DB マイグレーションと初期シード
   - コミット: `07e94e1 データベース定義と初期データを追加`
5. フロント / CMS の画面デザイン
   - Latte テンプレート、CSS、JS を追加
   - コミット: `ebda283 フロントとCMSの画面デザインを追加`
6. 主要機能
   - フロント: トップ、記事詳細、カテゴリ、タグ、検索、固定ページ、問い合わせ、状態ページ
   - CMS: ログイン、ダッシュボード、記事、プレビュー、履歴、固定ページ、分類、メディア、問い合わせ、設定、ゴミ箱
   - コミット: `50fc63d 主要機能を実装`
7. PHPUnit テストケース
   - コミット: `ed25ca3 テストケースを追加`
8. README に開発手順を追加
   - コミット: `49a91cb 開発手順を追加`
9. 作業状況の記録ルール追加
   - コミット: `cc993cc 作業状況の記録ルールを追加`
10. Docker 検証と PHP 8.5 対応
   - `dunglas/frankenphp:php8.5` へ変更
   - Docker イメージビルド成功
   - `composer install` 成功、`composer.lock` 生成
   - Phinx マイグレーション成功
   - 初期シード成功
   - PHPUnit 成功
   - フロント/CMS主要URLの HTTP 200 確認成功
   - コミット: `3bb9bc7 PHP8.5環境でDocker検証を完了`
11. GitHub MCP 設定確認
   - `codex mcp get github` で GitHub MCP サーバー登録済みを確認
   - `GITHUB_PAT_TOKEN` が非対話シェルで読まれない位置にあったため、`~/.bashrc` の先頭へ移動
   - GitHub API で指定 private repository と指定 branch が HTTP 200 で参照可能なことを確認

## 検証結果

- `docker compose config`: 成功
- `docker compose build`: 成功
- `docker compose run --rm app composer install`: 成功
- `docker compose run --rm app composer migrate`: 成功
- `docker compose run --rm app composer seed`: 成功
- `docker compose run --rm app composer test`: 成功
  - PHP: 8.5.6
  - PHPUnit: 9 tests, 14 assertions
- `curl -I http://localhost:8080/`: 200 OK
- `curl -I http://localhost:8080/admin/login`: 200 OK
- `curl -I http://localhost:8080/articles/start-small-webmedia`: 200 OK
- `codex mcp get github`: 成功
- GitHub API private repository 確認: 成功
  - `l-freeze/laravel10-clean-architecture` / `develop`: 200 OK
  - `l-freeze/learning-topics-php` / `learn/extra001-shared-module`: 200 OK

## 未完了・要検証

- ブラウザでの目視確認は未実施。
- CMS ログイン後の操作確認は未実施。
- 問い合わせ POST、記事保存、履歴復元などのブラウザ操作系は未実施。

## 次回再開地点

1. ブラウザで `http://localhost:8080/` と `http://localhost:8080/admin/login` を目視確認する。
2. CMS に `admin@example.test` / `password` でログインし、記事作成・編集・プレビュー・履歴を確認する。
3. フロントのカテゴリ、タグ、検索、問い合わせを確認する。
4. 見つかった不具合を機能単位で修正し、日本語コミットを作成する。

## 現在の注意点

- `docs/` 配下の元資料は未追跡のまま存在する。今回の実装コミットには含めていない。
- `dunglas/frankenphp:php8.5` と `dunglas/frankenphp:1-php8.5` は manifest 存在確認済み。`latest-php8.5` は存在しない。
- Caddy の 308 リダイレクトは解消済み。原因は Dockerfile が Caddyfile を `/etc/caddy/Caddyfile` にコピーしていたこと。
- 開発用 Caddyfile は `/etc/frankenphp/Caddyfile` に配置し、`auto_https off` を設定している。
- GitHub MCP の設定は完了済み。ただし、現在実行中の Codex セッションには後から MCP ツールが露出しないため、GitHub MCP ツールとして利用するには Codex セッションの再起動が必要。
