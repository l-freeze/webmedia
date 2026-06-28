# 現在の作業状況

## 更新日時

- 2026-06-28

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
- 問い合わせ管理:
  - 一覧を 100 件ページングにした。
  - 件名と操作列の詳細ボタンから `/admin/inquiries/{id}` を開けるようにした。
  - 状態は記事管理と同じ badge 表示にした。
  - 問い合わせ入力は名前 16 文字、件名 100 文字、内容 1000 文字を上限にした。
  - HTTP 経由で問い合わせ送信、CMS 一覧、詳細表示を確認済み。検証用問い合わせは削除済み。
- 監査ログ粒度:
  - 操作名だけでは不足するため、重要な更新では before / after の要約を残す方針にした。
  - ただし本文全文、問い合わせ本文、password、token、cookie、session id、過度な個人情報は Audit に残さない。
- 監査ログ管理:
  - `/admin/audit-logs` にページング UI、表示件数、日時昇順/降順ソート、日時/結果/操作/対象/ID/ユーザーの絞り込みを追加した。
  - 表示条件は Application の Query port へ検索条件として渡し、JSONL 実装側で絞り込み後に count / slice する形にした。
- マジックナンバー整理:
  - production PHP 内の HTTP status は `Response::HTTP_*` に寄せた。
  - 問い合わせ文字数上限は Application の `InquiryInputLimits` に集約し、Front template へ渡す形にした。
  - 抜粋長、関連記事件数、Audit 表示上限、フィルタ入力長、CSRF token bytes、cache key 長、upload サイズ、directory permission など、各 source に閉じる値は名前付き定数へ置き換えた。
- docs の判断基準:
  - 作業順序は `docs/plan.md`、機能差分は `docs/feature_inventory.md`、判断理由は `docs/decisions.md` を正とし、同じ判断基準を複数文書へ複製しない方針にした。
- CMS 機能不足対応:
  - 固定ページ編集画面の履歴欄から `/admin/pages/{id}/restore/{version}` で復元できるようにした。
  - 問い合わせ詳細画面から状態と管理メモを更新できるようにした。
  - 固定ページ復元画面と問い合わせ詳細画面は HTTP GET で導線を確認済み。実データ更新を避けるため POST の手動実行はしていない。
- 管理者 / ロール / パーミッション管理:
  - `/admin/users` を追加し、管理者の一覧、作成、更新、削除ができるようにした。
  - `/admin/roles` を追加し、ロール別パーミッションを読み取り専用で確認できるようにした。
  - `/admin/permissions` を追加し、パーミッション別の付与ロールを読み取り専用で確認できるようにした。
  - 既存の `users.role`、`RoleCode`、`PermissionCode` を使い、`admin_user.manage` / `role.view` / `permission.view` は admin のみに付与した。
  - 最後の admin ロール変更/削除、ログイン中ユーザー自身の削除を UseCase で防ぐようにした。
  - 管理者の作成/更新/削除は `AuditEvent` に記録する。password は監査ログに残さない。
  - 左メニューはゴミ箱までをコンテンツ運用系とし、その下に管理者、ロール、パーミッション、監査ログ、サイト設定を置く形にした。
  - PHPUnit、PHPStan、管理者/ロール/パーミッション各画面の HTTP GET、HTTP 経由の管理者作成/更新/削除を確認済み。検証用アカウントは削除済み。
  - 管理者アカウントの永続化 port は Application 配下ではなく `Domain/Repository/IAdminUserRepository` に置き、保存対象は `Domain/Entity/AdminUser` として扱う形へ整理した。
  - 問い合わせ状態更新の永続化 port も `Domain/Repository/IInquiryRepository` へ移し、Application 配下に Repository port を置かない形へ整理した。
- CMS 記事本文リッチエディター:
  - 記事編集画面の本文入力を Markdown / HTML / リッチエディターから選べるようにした。
  - リッチエディターは Tiptap OSS packages を pnpm / Vite で自前ビルドし、`public/assets/js/cms-rich-editor.js` として記事編集画面だけで読み込む。
  - Tiptap StarterKit の heading shortcut を使い、`# ` / `## ` / `### ` で見出し化できるようにした。
  - 公式 Notion-like demo の `/` 候補表示は StarterKit 標準ではないため、段落、見出し、リスト、引用、コード、区切り線、表、画像の簡易 slash menu を自前実装した。
  - リンクはエディター上で青色下線表示にし、文字列を範囲選択した状態で URL をペーストするとリンク化できるようにした。
  - リッチエディター内のリンククリックは編集操作として扱い、記事編集画面から外部遷移しないようにした。
  - 文字列選択時にポップアップの装飾メニューを表示し、太字、斜体、下線、取り消し線、コード、リンク設定、リンク解除を選択範囲に適用できるようにした。
  - マウスで範囲選択する場合はドラッグ中に装飾メニューを出さず、クリックを離した後に表示するようにした。キーボード選択では従来どおり selection update に追従する。
  - 画像挿入は URL 手入力だけではなく、CMS 登録済み画像を検索・選択して本文へ挿入できるようにした。
  - 区切り線とテーブル挿入/行追加/列追加/行削除/列削除/表削除の toolbar 操作を追加した。
  - `body_format=rich_text` を追加し、本文は Tiptap / ProseMirror JSON 文字列として既存 `body_markdown` に保存する。
  - `body_html` を表示用 HTML の派生 cache として追加し、CMS 保存時に `body_markdown` + `body_format` から安全な HTML を生成して保存する。Front は `body_html` を優先し、未生成の既存行では従来どおり source から fallback 変換する。
  - CMS 編集画面では Markdown / HTML / rich_text の形式別ドラフトをブラウザ上で保持し、リッチエディターから Markdown / HTML へ切り替えた時に textarea を JSON で上書きしない。
  - 公開表示・CMSプレビューでは PHP 側の Tiptap JSON renderer が許可 node / mark だけ HTML 化し、既存 HTMLPurifier を通す。表、区切り線、画像、リンクも許可範囲内でレンダリングする。
  - Tiptap Cloud、AI Toolkit、Collaboration、Pro Extensions、有料 Notion-like template は採用しない判断を `docs/decisions.md` に記録した。
  - `resources/cms/rich-editor.js` は Tiptap 初期化と各 UI controller の結線に絞り、本文変換、DOM helper、画像ピッカー、選択範囲装飾メニュー、slash menu、toolbar、本文形式切替を `resources/cms/rich-editor/` 配下の ES modules に分けた。Vite で従来通り `public/assets/js/cms-rich-editor.js` 1本へ bundle する。

## 次回再開地点

- 作業順序は `docs/plan.md` を正とする。
- 機能差分は `docs/feature_inventory.md` を正とする。

## 注意点

- 未追跡の `docs/php85-optimize.md` はユーザー作業の可能性があるため触っていない。
- 未関連変更として `CLAUDE.md`、`apps/minimal/`、`bin/memo.txt` が残っている。
- ヘッドレス Chrome 実行環境では日本語フォント不足により日本語が豆腐表示になる。HTML の文字化けではなく、レイアウト幅・重なり・横スクロール確認として扱う。
