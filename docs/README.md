# 各資料について
- 機能棚卸しと次の優先候補について
  - ./docs/feature_inventory.mdを参照する事
- 技術スタックについて
  - ./docs/webmedia_technical_stack.mdを参照する事
- アーキテクチャーについて
  - `front` / `cms` をアプリケーション単位で分離する
  - Application は `<対象>/<ユースケース>` 配下に `Input` / `Interactor` / `Output` を置く
  - Query interface は各ユースケース配下、Repository interface は Domain 配下に置く
  - Front の HTTP Controller や SEO meta は Presentation 層に置く
  - 詳細は ./docs/webmedia_architecture_rules.md を参照する事
  - Application Port の判断基準は ./docs/application_ports.md を参照する事
- 監査ログミドルウェアについて
  - ./docs/audit_middleware.mdを参照する事
- middleware 境界の見直し論点について
  - ./docs/middleware_boundary_review.mdを参照する事
- 要件について
  - ユーザーフロントについて
    - ./docs/webmedia_ui_requirements_final_markdown/webmedia_front_ui_requirements_final.mdを参照する事
  - CMSについて
    - ./docs/webmedia_ui_requirements_final_markdown/webmedia_cms_ui_requirements_final.mdを参照する事
- デザインについて
  - ./docs/webmedia_html_css_and_screenshots_all/webmedia_html_design/README.mdを参照する事
  - 機能を実装するために必要な改変や、ベターなUI/UXのための改変であれば許可する

## docs の扱い

- 現行判断を確認する場合は `feature_inventory.md`、`current_status.md`、`plan.md`、`webmedia_architecture_rules.md` を優先する。
- `review.md`、`refactoring.md`、`knowledge.md`、`if_story_*.md` は判断履歴・参考資料であり、現在の作業指示としては扱わない。
- docs 配下の重複や古い判断を見つけた場合は、削除より先に `feature_inventory.md` か該当する現行規約へ有効な内容を移してから整理する。
