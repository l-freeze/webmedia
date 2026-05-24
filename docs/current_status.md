# 現在の作業状況

## 更新日時

- 2026-05-24

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
12. アーキテクチャ規約の更新
   - `Application/<対象>/<ユースケース>/{Input,Interactor,Output}` を標準構造として明記
   - Query interface は各 UseCase 配下、Repository interface は Domain 配下に置く方針へ更新
   - `QueryService` / UseCase `Handler` 命名を廃止し、`I...Query` / `I...Repository` / `Interactor` に統一する方針を明記
13. アーキテクチャ規約に沿ったコード再配置
   - `front` / `cms` の Controller を Application Interactor 呼び出しへ変更
   - `QueryService` interface を廃止し、UseCase 配下の `I...Query` へ移行
   - Domain Repository interface を `Domain/Repository/I...Repository` へ移行
   - PDO 実装を `Pdo...Query` と `Pdo...Repository` に分離
14. Value / Entity ルールへの追従
   - Query の戻り値を UseCase 配下の Value DTO、または `array<Article>` など phpdoc 付きの Value DTO 配列へ変更
   - `...List` Value DTO を廃止し、一覧は要素 Value の配列で表現
   - CMS `Article` Entity を `Domain/Value` の ValueObject で構成
   - Repository interface の戻り値を Domain Entity / Domain Value に寄せ、Auth User も Entity 化
15. PSR 関連の再検証と修正
   - `composer dump-autoload -o --strict-psr` で PSR-4 autoload 違反がないことを確認
   - `Container::has()` の factory / singleton / binding 判定を PSR-11 風の期待挙動に修正
   - `Container::has()` の factory / singleton / binding 用テストを追加
16. CMS 記事管理 UI の改善
   - 記事一覧のタイトルリンクから編集画面へ遷移できるように変更
   - 記事編集画面の公開日時 / 予約公開日時に flatpickr の日時 picker を追加
   - CMS layout に画面単位の head / scripts block を追加
17. CMS 保存フィードバックと taxonomy 保存エラー修正
   - 保存ボタン押下時に `保存中...` 表示と disabled 状態へ切り替える UI を追加
   - 保存後に `保存しました` の toast を表示し、数秒後に自動で消す UI を追加
   - taxonomy の未入力保存を DB 例外にせず、422 とエラーメッセージで返すように修正
18. CMS PRG ルールの明文化と検証
   - CMS 更新系 HTTP 処理は成功時に PRG、Post Redirect Get、を使うルールをアーキテクチャ規約へ追記
   - 画面表示 UseCase は `Edit`、作成/更新の永続化 UseCase は `Save` を使ってよいことを明記
   - HTTP メソッド名に合わせるだけの `Post` / `Update` UseCase 乱立は不要と整理

## 検証結果

- `docker compose config`: 成功
- `docker compose build`: 成功
- `docker compose run --rm app composer install`: 成功
- `docker compose run --rm app composer migrate`: 成功
- `docker compose run --rm app composer seed`: 成功
- `docker compose run --rm app composer test`: 成功
  - PHP: 8.5.6
  - PHPUnit: 9 tests, 14 assertions
- `docker compose exec app composer test`: 成功
  - PHP: 8.5.6
  - PHPUnit: 9 tests, 14 assertions
- `docker compose exec app composer test`: 成功
  - PHP: 8.5.6
  - PHPUnit: 9 tests, 14 assertions
  - Value / Entity ルール追従後に再実行
- `docker compose exec app composer test`: 成功
  - PHP: 8.5.6
  - PHPUnit: 10 tests, 18 assertions
  - PSR 関連の再検証後に再実行
- `docker compose exec app composer validate --strict`: 成功
- `docker compose exec app composer dump-autoload -o --strict-psr`: 成功
  - PSR-4 autoload 違反なし
- PHP ファイル先頭形式チェック: 成功
  - `<?php`
  - 空行
  - `declare(strict_types=1);`
- PHP ファイルの閉じタグ、タブ、行末空白チェック: 成功
  - 検出なし
- `docker compose exec app sh -lc 'find apps/front/src apps/cms/src apps/front/config apps/cms/config tests -type f -name "*.php" -print0 | xargs -0 -n1 php -l'`: 成功
- `docker compose exec app composer test`: 成功
  - PHP: 8.5.6
  - PHPUnit: 10 tests, 18 assertions
  - CMS 記事管理 UI 改善後に再実行
- `docker compose exec app sh -lc 'find apps src tests database public -type f -name "*.php" -print0 | xargs -0 -n1 php -l'`: 成功
  - CMS 記事管理 UI 改善後に再実行
- CMS 記事管理 UI 改善後の HTTP 確認: 成功
  - `/admin/articles`: ログイン後 200 OK
  - `/admin/articles/3`: ログイン後 200 OK
  - 記事タイトルリンクと flatpickr 読み込みタグの出力を確認
- `docker compose exec app composer test`: 成功
  - PHP: 8.5.6
  - PHPUnit: 10 tests, 18 assertions
  - CMS 保存フィードバック / taxonomy エラー修正後に再実行
- `docker compose exec app sh -lc 'find apps src tests database public -type f -name "*.php" -print0 | xargs -0 -n1 php -l'`: 成功
  - CMS 保存フィードバック / taxonomy エラー修正後に再実行
- taxonomy 未入力保存確認: 成功
  - `POST /admin/taxonomy`: 422
  - `カテゴリ名とスラッグを入力してください。` を表示
- taxonomy 正常保存後の toast 確認: 成功
  - `POST /admin/taxonomy`: 302
  - redirect 後に `保存しました` toast を表示
  - 検証用に追加した一時カテゴリは確認後に削除
- CMS PRG 確認: 成功
  - `POST /admin/articles`: 302、`/admin/articles/{id}` へ redirect
  - `POST /admin/pages`: 302、`/admin/pages` へ redirect
  - `POST /admin/taxonomy`: 302、`/admin/taxonomy` へ redirect
  - `POST /admin/settings`: 302、`/admin/settings` へ redirect
  - validation error の `POST /admin/taxonomy`: 422、永続化せず同一画面表示
  - 検証用に追加した一時記事、固定ページ、カテゴリは確認後に削除
- Application Interactor 経由の主要 URL 確認: 成功
  - `/`: 200 OK
  - `/articles/start-small-webmedia`: 200 OK
  - `/categories`: 200 OK
  - `/categories/development`: 200 OK
  - `/tags/php`: 200 OK
  - `/search?q=PHP`: 200 OK
  - `/pages/about`: 200 OK
  - `/contact`: 200 OK
  - `/admin/login`: 200 OK
  - `/admin`: 未ログイン時 302、ログイン後 200 OK
  - `/admin/articles`: ログイン後 200 OK
  - `/admin/articles/1`: ログイン後 200 OK
  - `/admin/articles/1/preview`: ログイン後 200 OK
  - `/admin/articles/1/history`: ログイン後 200 OK
  - `/admin/pages`: ログイン後 200 OK
  - `/admin/taxonomy`: ログイン後 200 OK
  - `/admin/settings`: ログイン後 200 OK
- `curl -I http://localhost:8080/`: 200 OK
- `curl -I http://localhost:8080/admin/login`: 200 OK
- `curl -I http://localhost:8080/articles/start-small-webmedia`: 200 OK
- `codex mcp get github`: 成功
- GitHub API private repository 確認: 成功
  - `l-freeze/laravel10-clean-architecture` / `develop`: 200 OK
  - `l-freeze/learning-topics-php` / `learn/extra001-shared-module`: 200 OK

## 未完了・要検証

- ブラウザでの目視確認は未実施。
- CMS ログイン後の主要 GET 画面確認は実施済み。
- 問い合わせ POST、記事保存、履歴復元などの更新系操作確認は未実施。

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
