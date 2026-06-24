# 機能棚卸し

## 目的

この文書は、要件資料と現行実装の差分を一覧化し、次に実装・検証・整理する作業を選びやすくするための棚卸しである。

詳細要件は以下を正とする。

- CMS UI 要件: `docs/webmedia_ui_requirements_final_markdown/webmedia_cms_ui_requirements_final.md`
- Front UI 要件: `docs/webmedia_ui_requirements_final_markdown/webmedia_front_ui_requirements_final.md`
- 技術・構成要件: `docs/webmedia_technical_stack.md`
- アーキテクチャ規約: `docs/webmedia_architecture_rules.md`

## 判定

- 完了: 主要な route / Controller / UseCase / template があり、MVP として動く形がある。
- 一部完了: 主要部品はあるが、要件の一部、運用、検証、または UI が不足している。
- 未実装: 要件はあるが、現行 route / UseCase / template として確認できない。
- 要検証: 実装はあるが、ブラウザ操作、更新系 HTTP、または本番想定の確認が不足している。

## CMS

| 機能 | 状態 | 現行実装 | 残作業 |
|---|---|---|---|
| ログイン / ログアウト | 完了 | `/admin/login`, `/admin/logout`, Login UseCase, `CmsGuard`, SecurityLog | 試行回数制限、IP 制限、2FA は公開前課題 |
| ダッシュボード | 完了 | `/admin`, Dashboard Show UseCase / template | 実データでの表示確認 |
| 記事一覧 | 完了 | `/admin/articles`, Article List UseCase / template | 絞り込み・検索・ページングの要件充足度確認 |
| 記事作成 / 編集 / 保存 | 完了 | create/edit/store/update routes, Article Save UseCase, validation, history 保存 | 更新系ブラウザ確認、Business Audit |
| 公開 / 非公開 / 公開予約 / 公開終了 | 一部完了 | Article status, `published_at`, `published_ends_at` 入力 | 予約公開の運用確認、公開状態の UI 表示確認 |
| 記事プレビュー | 完了 | `/admin/articles/{id}/preview` | 下書きプレビュー URL の保護強化は公開前課題 |
| 記事更新履歴 / 版プレビュー / 復元 | 一部完了 | history, version preview, restore routes / UseCase | 差分比較 UI は未確認。復元の更新系操作確認 |
| 固定ページ管理 | 一部完了 | list/create/edit/store/update routes, Page UseCase | 固定ページ履歴 / 復元 UI は route 未接続。複製は未実装 |
| カテゴリ管理 | 完了 | list/store/update/delete routes, Category UseCase | SEO 詳細編集は未実装 |
| タグ管理 | 完了 | list/store/update/delete routes, Tag UseCase | SEO 詳細編集は未実装 |
| メディア管理 | 一部完了 | list/upload/delete routes, Media UseCase | 使用先表示、画像 alt 編集、ストレージ移行方針は未実装 |
| 問い合わせ管理 | 一部完了 | `/admin/inquiries`, Inquiry List UseCase | 詳細、既読/対応状態、削除、通知運用は未実装 |
| サイト設定 | 完了 | `/admin/settings`, Setting Edit/Save UseCase | OGP / RSS / sitemap 関連設定は未実装 |
| ゴミ箱 | 一部完了 | `/admin/trash`, Trash List UseCase, article restore | 固定ページ等を含む復元/完全削除の扱い確認 |
| 認証・認可 | 一部完了 | `CurrentCmsUser`, `AuthorizationService`, route-level permission, `ArticlePolicy` | template helper、Article 以外の UseCase-level authorization 拡張 |
| Audit / SecurityLog | 一部完了 | HTTP 状態変更 Audit、認可/CSRF/login/logout SecurityLog | 業務イベントの Business Audit は未実装 |
| PC/SP 表示 | 要検証 | CMS template / CSS は存在 | Playwright またはブラウザで主要画面の目視確認 |

## Front

| 機能 | 状態 | 現行実装 | 残作業 |
|---|---|---|---|
| トップページ | 完了 | `/`, Home Show UseCase, pagination test | 実データでの目視確認 |
| 記事詳細 | 完了 | `/articles/{slug}`, Article Show UseCase, canonical | 関連記事、人気記事、更新日 UI の充足度確認 |
| カテゴリ一覧 / 詳細 | 完了 | `/categories`, `/categories/{slug}` | 実 HTTP 表示確認 |
| タグ一覧 / 詳細 | 完了 | `/tags`, `/tags/{slug}` | 実 HTTP 表示確認 |
| 検索結果 / 検索結果なし | 一部完了 | `/search`, Article Search UseCase | 検索機能 ON/OFF、検索結果なし表示の回帰テスト |
| 固定ページ | 完了 | `/pages/{slug}`, Page Show UseCase | 法務ページ本文の本番用整備 |
| 問い合わせ入力 / エラー / 完了 | 一部完了 | `/contact`, `/contact/success`, Inquiry Create UseCase | rate limit、honeypot/CAPTCHA、管理者通知、プライバシー同意文言 |
| 404 | 一部完了 | NotFound controller / state template | route からの実表示と本番表示確認 |
| ローディング / 記事なし状態 | 完了 | `/loading`, `/no-articles` state routes | 本番 route として残すか、デザイン確認専用にするか判断 |
| スマホメニュー | 完了 | `/menu` state route / template | 実画面導線との統合確認 |
| 共通レイアウト | 完了 | `LayoutGlobalProvider`, page links, settings, categories | cache invalidation は複雑化時に検討 |
| SEO / 配信 | 一部完了 | canonical は一部対応済み | OGP / Twitter Card、robots.txt、sitemap.xml、RSS / Atom |
| 公開前運用 | 未実装 | - | デプロイ、バックアップ、復元、障害時手順 |

## Shared / Framework

| 項目 | 状態 | 現行実装 | 残作業 |
|---|---|---|---|
| DI / HTTP / Routing / Middleware | 完了 | Framework と tests | route match 重複などは必要時に改善 |
| CSRF / Session | 完了 | Framework Security、CMS 差し替え middleware | worker mode での連続 request 検証 |
| Template rendering | 完了 | Latte renderer、compile cache 方針 | 本番 cache opt-in の運用判断 |
| Markdown / HTML sanitize | 完了 | Shared Markdown | UseCase 内で必要になったら contract 検討 |
| Logging / Audit / SecurityLog | 一部完了 | JSON Lines logger、EventBus | request id、例外ログ連携、Business Audit |
| Cache | 一部完了 | PSR-16 file/null cache | Front article cache は未適用 |
| Architecture Test | 完了 | 依存方向テストあり | `Input` named arguments / Controller alias など追加検査候補 |
| Scaffold | 完了 | scaffold tests | fullpack template は将来候補 |

## docs 棚卸し

### 常に参照する一次資料

| 文書 | 扱い |
|---|---|
| `docs/README.md` | docs の入口。参照先の索引として維持 |
| `docs/plan.md` | 作業計画と公開前評価。完了済み計画が混ざるため、今後はこの棚卸しと `current_status` へ寄せて肥大化を避ける |
| `docs/current_status.md` | 作業ログと再開地点。履歴が長いが、現状把握には必要 |
| `docs/webmedia_technical_stack.md` | 技術選定の一次資料として維持 |
| `docs/webmedia_architecture_rules.md` | アーキテクチャ規約の一次資料として維持 |
| `docs/webmedia_ui_requirements_final_markdown/*.md` | UI 要件の一次資料として維持 |
| `docs/webmedia_html_css_and_screenshots_all/webmedia_html_design/` | 初期デザイン参照。実装後も比較・目視確認用として維持 |

### 現行判断として残す設計資料

| 文書 | 扱い |
|---|---|
| `docs/application_ports.md` | Port 判断基準。現行規約として維持 |
| `docs/webmedia_architecture_rules.md` | 依存方向、配置、命名、middleware 境界の現行規約として維持 |

### 整理対象

| 対象 | 判断 |
|---|---|
| `docs/webmedia_architecture_rules.md:Zone.Identifier` | Windows の ZoneTransfer メタ情報であり、設計資料ではないため削除 |
| `docs/webmedia_technical_stack.md:Zone.Identifier` | Windows の ZoneTransfer メタ情報であり、設計資料ではないため削除 |
| `docs/php85-optimize.md` | 未追跡ファイル。ユーザー作業の可能性があるため今回は触らない |

## 次にやるとよい順番

1. Front / CMS の主要画面をブラウザで確認し、要件の「一部完了」「要検証」を実画面ベースで潰す。
2. 公開前必須に近い `robots.txt`、`sitemap.xml`、OGP / Twitter Card、RSS / Atom の方針を決める。
3. 問い合わせフォームの rate limit / spam 対策 / プライバシー同意文言 / 管理者通知を実装する。
4. CMS の Business Audit を記事保存・削除・復元、メディア削除、設定更新から追加する。
5. 固定ページ履歴 / 復元、問い合わせ詳細 / 対応状態など、CMS 運用で不足する機能を追加する。
6. 画面が揃った段階で、PC/SP のデザインリファインと Playwright 目視確認を行う。
