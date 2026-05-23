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

## 未完了・要検証

- Docker コンテナ内で依存ライブラリを導入する。
- Docker コンテナ内で PHPUnit を実行する。
- Docker Compose で DB 起動、マイグレーション、シードを実行する。
- Web アプリを起動し、フロントと CMS の主要画面を確認する。
- 検証で見つかった PHP 構文エラー、依存解決エラー、テンプレートエラー、SQL エラーを修正する。
- `composer.lock` はアプリケーションとして生成後にコミット対象にする。

## 次回再開地点

1. `docker compose config` で Compose 設定を確認する。
2. `docker compose build` または `make build` を実行する。
3. `docker compose run --rm app composer install` を実行する。
4. `docker compose up -d db` 後、`docker compose run --rm app composer migrate` と `composer seed` を実行する。
5. `docker compose run --rm app composer test` を実行する。
6. 失敗があれば修正し、機能単位で日本語コミットを作成する。

## 現在の注意点

- `docs/` 配下の元資料は未追跡のまま存在する。今回の実装コミットには含めていない。
- `docs/current_status.md` と `docs/plan.md` の運用ルール追記は、この後コミットする。
