# 現在の作業状況

## 更新日時

- 2026-06-26

## 現在地

- 機能棚卸しは `docs/feature_inventory.md` に集約済み。
- 作業順序は `docs/plan.md` に集約済み。
- docs は、現行判断に必要な資料だけを残す方針へ変更中。
- 過去レビュー、学習メモ、完了済みの詳細計画、将来構想だけの文書は削除し、Git 履歴に任せる。
- Front / CMS の主要 GET と、CMS / Front の主要更新系を HTTP 経由で確認済み。

## 直近作業

- `docs/README.md` を薄い索引に整理。
- `docs/plan.md` を現在の作業順序だけに整理。
- `docs/current_status.md` を現在地と再開地点だけに整理。
- `docs/feature_inventory.md` の docs 棚卸しを、残す資料と削除済み資料が分かる形に整理。
- 古い・重複した・実装済み計画系 docs を削除。
- 削りすぎた判断根拠を補うため、`docs/decisions.md` を追加。
  - View コンパイルキャッシュ
  - Layout Global Provider の cache
  - Middleware 境界
  - Route metadata
  - Audit / SecurityLog
  - Application Port
- Front:
  - `/`, 記事詳細、カテゴリ一覧/詳細、タグ一覧/詳細、固定ページ、問い合わせ、状態表示、404 を HTTP 確認。
  - `/search` は現在の設定では検索停止中として 404 を返すことを確認。
  - 問い合わせ送信は CSRF 付き POST で `/contact/success` へ遷移することを確認。
- CMS:
  - ログイン後にダッシュボード、記事、固定ページ、カテゴリ、タグ、メディア、問い合わせ、設定、ゴミ箱を HTTP 確認。
  - 記事作成、編集、プレビュー、履歴、版プレビュー、復元、削除を HTTP 経由で確認。
  - カテゴリ / タグの作成、更新、削除を HTTP 経由で確認。
  - 検証用データは削除済み。
- CMS 記事編集画面で `sr-only` radio input に共通 `w-full` が当たり横スクロールが出ていたため、CMS layout の Presentation 側で修正。
- BusinessAudit という別ログ種別は作らず、既存の `AuditEvent` に業務イベントを集約する方針にした。
- `/admin/audit-logs` を追加し、`storage/logs/audit.log` を CMS から確認できるようにした。
- 記事作成・更新・削除・復元、メディア削除、設定更新の成功時に業務 AuditEvent を記録するようにした。
- `article.created` / `article.trashed` は HTTP 経由で記録と表示を確認済み。検証用記事は削除済み。

## 次回再開地点

1. `robots.txt`、`sitemap.xml`、OGP / Twitter Card、RSS / Atom の仕様を決める。場当たり的な仮実装はしない。
2. 問い合わせフォームの最低限の rate limit / spam 対策 / プライバシー同意文言を追加する。
3. 固定ページ履歴 / 復元、問い合わせ詳細 / 対応状態など、CMS 運用で不足する機能を追加する。

## 注意点

- 未追跡の `docs/php85-optimize.md` はユーザー作業の可能性があるため触っていない。
- 未関連変更として `CLAUDE.md`、`apps/minimal/`、`bin/memo.txt` が残っている。
- ヘッドレス Chrome 実行環境では日本語フォント不足により日本語が豆腐表示になる。HTML の文字化けではなく、レイアウト幅・重なり・横スクロール確認として扱う。
