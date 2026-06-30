# WEBメディア実装計画

## 前提

- 作業前に `docs/current_status.md` を確認する。
- 作業完了時は `docs/current_status.md` を更新する。
- PHP / Composer はホストではなく Docker Compose 経由で実行する。
- `apps/cms` と `apps/front` の業務ロジックは分離する。
- Framework は HTTP / DI / Routing / View / Security などの薄い基盤に留める。
- 詳細な構造規約は `docs/webmedia_architecture_rules.md` を正とする。

## 現在の優先順

1. 公開前必須に近い `robots.txt`、`sitemap.xml`、OGP / Twitter Card、RSS / Atom の仕様を決める。場当たり的に仮実装しない。
2. 画像変換処理を設計する。既に保存している width / height / thumbnail_path / optimized_path を使い、サムネイル生成と最適化画像生成の仕様を決めてから実装する。
3. CMS / Front の PC/SP 表示を、日本語フォントがある環境または Playwright 環境で再確認する。

## 後で忘れず判断すること

- 問い合わせフォームの最低限の rate limit / spam 対策 / プライバシー同意文言。積極運用しない前提なので直近優先にはしないが、公開フォームとして外部入力口になるため公開前には判断する。

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
- CMS 状態変更 Audit middleware と、記事/固定ページ/分類/問い合わせ/管理者/設定/メディア削除の業務要約。
- 問い合わせ一覧の 100 件ページング、問い合わせ詳細画面、問い合わせ入力の文字数上限。
- 固定ページ編集画面からの履歴復元。
- 問い合わせ詳細画面からの状態更新と管理メモ保存。

## 公開前に必ず確認すること

- `APP_DEBUG=false`
- HTTPS
- seed の初期管理者アカウントを本番に残さない
- 管理画面の追加防御
- 管理ログインの試行回数制限
- DB と uploads のバックアップ / 復元手順
- `robots.txt`
- `sitemap.xml`
- OGP / Twitter Card
- RSS / Atom
- 問い合わせフォームの spam 対策と個人情報取得説明
- 本番デプロイ、ロールバック、障害時確認手順
