# docs

作業開始時は、まず以下を確認する。

1. `docs/current_status.md`
2. `docs/plan.md`
3. `docs/feature_inventory.md`
4. `docs/webmedia_architecture_rules.md`

## Framework 名

本プロジェクトの軽量 PHP Framework は `pera` と呼ぶ。

`pera` は `src/Framework` に置く。HTTP / DI / Routing / View / Security / Process など、アプリケーション都合を持たない薄い実行基盤を担当する。`apps/kernel`、`apps/shared`、`apps/front`、`apps/cms` は `pera` の外側にあるプロジェクト側の層として扱う。

## 主要資料

- `docs/current_status.md`: 現在地、未完了、次回再開地点。
- `docs/plan.md`: 直近の作業順序。
- `docs/feature_inventory.md`: CMS / Front / Shared / Framework の機能棚卸し。
- `docs/decisions.md`: なぜやる / やらないの設計判断記録。
- `docs/site_model_plan.md`: `sites` を中心にした公開面モデルの追加方針。
- `docs/architecture_boundary_review.md`: 責務・境界・構造の全体精査で抽出した検証待ち候補。
- `docs/system_architecture_review_20260717.md`: システム全体の品質属性・実行時意味論・運用境界を含む総合レビューと改善候補。トリアージ後は正規文書へ移管する。
- `docs/webmedia_architecture_rules.md`: アーキテクチャ規約。
- `docs/application_ports.md`: Application Port の判断基準。
- `docs/webmedia_technical_stack.md`: 技術スタック。
- `docs/webmedia_ui_requirements_final_markdown/`: Front / CMS の UI 要件。
- `docs/webmedia_html_css_and_screenshots_all/webmedia_html_design/`: 初期デザイン参照。

## 整理方針

- 完了済みの実装計画、過去レビュー、学習メモ、将来構想だけの文書は残さない。
- 「なぜやる / やらない」の根拠は `decisions.md` に残す。
- 現行判断として必要な内容は、規約なら `webmedia_architecture_rules.md`、作業判断なら `plan.md`、機能差分なら `feature_inventory.md`、判断理由なら `decisions.md` に寄せる。
- 同じ判断基準を複数の文書へ複製しない。重複が出た場合は、正となる文書を一つ選び、他の文書は参照または状態記録に留める。
- docs を増やす前に、既存資料へ追記できないか確認する。
