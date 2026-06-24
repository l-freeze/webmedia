# 現在の作業状況

## 更新日時

- 2026-06-24

## 現在地

- 機能棚卸しは `docs/feature_inventory.md` に集約済み。
- 作業順序は `docs/plan.md` に集約済み。
- docs は、現行判断に必要な資料だけを残す方針へ変更中。
- 過去レビュー、学習メモ、完了済みの詳細計画、将来構想だけの文書は削除し、Git 履歴に任せる。

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

## 次回再開地点

1. `docs/feature_inventory.md` の「一部完了」「要検証」を上から確認する。
2. まず Front / CMS の主要画面をブラウザで確認する。
3. 更新系操作として、記事作成・編集・プレビュー・履歴復元、問い合わせ送信を確認する。
4. 見つかった不具合を機能単位で修正する。

## 注意点

- 未追跡の `docs/php85-optimize.md` はユーザー作業の可能性があるため触っていない。
- 未関連変更として `CLAUDE.md`、`apps/minimal/`、`bin/memo.txt` が残っている。
