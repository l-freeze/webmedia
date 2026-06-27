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
| ダッシュボード | 完了 | `/admin`, Dashboard Show UseCase / template | 実データで HTTP 表示確認済み |
| 記事一覧 | 完了 | `/admin/articles`, Article List UseCase / template | HTTP 表示確認済み。絞り込み・検索・ページングの要件充足度確認は必要時に行う |
| 記事作成 / 編集 / 保存 | 完了 | create/edit/store/update routes, Article Save UseCase, validation, history 保存, 業務 AuditEvent, Markdown / Tiptap リッチエディター切替 | HTTP 経由で作成/編集確認済み。リッチエディターは OSS Tiptap を自前ビルドし、JSON を PHP renderer + HTMLPurifier 経由で表示 |
| 公開 / 非公開 / 公開予約 / 公開終了 | 一部完了 | Article status, `published_at`, `published_ends_at` 入力 | 入力 UI は確認済み。予約公開の運用確認、公開状態の UI 表示確認 |
| 記事プレビュー | 完了 | `/admin/articles/{id}/preview` | 下書きプレビュー URL の保護強化は公開前課題 |
| 記事更新履歴 / 版プレビュー / 復元 | 一部完了 | history, version preview, restore routes / UseCase | HTTP 経由で履歴/版プレビュー/復元確認済み。差分比較 UI は未実装 |
| 固定ページ管理 | 一部完了 | list/create/edit/store/update/restore routes, Page UseCase, 編集画面の履歴復元 UI | 複製は未実装 |
| カテゴリ管理 | 完了 | list/store/update/delete routes, Category UseCase | HTTP 経由で作成/更新/削除確認済み。SEO 詳細編集は未実装 |
| タグ管理 | 完了 | list/store/update/delete routes, Tag UseCase | HTTP 経由で作成/更新/削除確認済み。SEO 詳細編集は未実装 |
| メディア管理 | 一部完了 | list/upload/delete routes, Media UseCase | 使用先表示、画像 alt 編集、ストレージ移行方針は未実装 |
| 問い合わせ管理 | 一部完了 | `/admin/inquiries`, `/admin/inquiries/{id}`, Inquiry List/Detail/Update UseCase, 100件ページング, 状態更新, 管理メモ | 削除、通知運用は運用方針が固まってから判断 |
| サイト設定 | 完了 | `/admin/settings`, Setting Edit/Save UseCase | OGP / RSS / sitemap 関連設定は未実装 |
| ゴミ箱 | 一部完了 | `/admin/trash`, Trash List UseCase, article restore | 固定ページ等を含む復元/完全削除の扱い確認 |
| 管理者管理 | 完了 | `/admin/users`, AdminUser List/Create/Update/Delete UseCase, `admin_user.manage` permission, 業務 AuditEvent | HTTP 経由で作成/更新/削除確認済み。検証用アカウントは削除済み |
| ロール管理 | 完了 | `/admin/roles`, Role List UseCase, `role.view` permission, コード定義ロールの権限表示 | 現時点では読み取り専用。ロールをDB管理にするかは運用要件が出た時点で判断 |
| パーミッション管理 | 完了 | `/admin/permissions`, Permission List UseCase, `permission.view` permission, パーミッション別付与ロール表示 | 現時点では読み取り専用。権限付与をDB管理にするかは運用要件が出た時点で判断 |
| 認証・認可 | 一部完了 | `CurrentCmsUser`, `AuthorizationService`, route-level permission, `ArticlePolicy`, 管理系 permissions | template helper、Article 以外の UseCase-level authorization 拡張 |
| Audit / SecurityLog | 一部完了 | HTTP 状態変更 Audit、記事/メディア/設定の業務 AuditEvent、認可/CSRF/login/logout SecurityLog、`/admin/audit-logs` のページング/日時ソート/絞り込み | 記事/設定/メディアの before / after 要約 |
| PC/SP 表示 | 一部完了 | CMS template / CSS は存在 | ヘッドレス Chrome で主要画面を確認し、記事編集の横スクロールを修正済み。日本語フォントありの環境で再確認 |

## Front

| 機能 | 状態 | 現行実装 | 残作業 |
|---|---|---|---|
| トップページ | 完了 | `/`, Home Show UseCase, pagination test | HTTP / ヘッドレス Chrome で実データ表示確認済み |
| 記事詳細 | 完了 | `/articles/{slug}`, Article Show UseCase, canonical | HTTP 表示確認済み。関連記事、人気記事、更新日 UI の充足度確認は必要時に行う |
| カテゴリ一覧 / 詳細 | 完了 | `/categories`, `/categories/{slug}` | HTTP 表示確認済み |
| タグ一覧 / 詳細 | 完了 | `/tags`, `/tags/{slug}` | HTTP 表示確認済み |
| 検索結果 / 検索結果なし | 一部完了 | `/search`, Article Search UseCase | 現在設定では検索停止中として 404 を返すことを確認。検索 ON 時の結果なし表示は未確認 |
| 固定ページ | 完了 | `/pages/{slug}`, Page Show UseCase | 法務ページ本文の本番用整備 |
| 問い合わせ入力 / エラー / 完了 | 一部完了 | `/contact`, `/contact/success`, Inquiry Create UseCase, 文字数上限 | CSRF 付き POST と完了遷移は確認済み。rate limit、honeypot/CAPTCHA、プライバシー同意文言は公開前の保留判断。管理者通知は運用方針が固まってから判断 |
| 404 | 完了 | NotFound controller / state template | route からの 404 表示確認済み |
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
| Markdown / HTML sanitize | 完了 | Shared Markdown, Tiptap JSON renderer | UseCase 内で必要になったら contract 検討。rich_text は許可 node / mark のみ HTML 化 |
| Logging / Audit / SecurityLog | 一部完了 | JSON Lines logger、EventBus、CMS Audit 閲覧、監査ログのページング/日時ソート/絞り込み | request id の運用、例外ログ連携、記事/設定/メディアの before / after 要約 |
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
| `docs/decisions.md` | なぜやる / やらないの設計判断記録として維持 |
| `docs/webmedia_architecture_rules.md` | 依存方向、配置、命名、middleware 境界の現行規約として維持 |

### 整理対象

| 対象 | 判断 |
|---|---|
| `docs/webmedia_architecture_rules.md:Zone.Identifier` | Windows の ZoneTransfer メタ情報であり、設計資料ではないため削除 |
| `docs/webmedia_technical_stack.md:Zone.Identifier` | Windows の ZoneTransfer メタ情報であり、設計資料ではないため削除 |
| `docs/php85-optimize.md` | 未追跡ファイル。ユーザー作業の可能性があるため今回は触らない |

## 作業順序

作業順序は `docs/plan.md` を正とする。この文書には機能差分だけを残し、優先順位の判断基準は複製しない。
