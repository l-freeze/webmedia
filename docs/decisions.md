# 設計判断記録

この文書は「なぜそうするのか」「なぜ今はやらないのか」を残す場所である。

実装手順、過去レビュー、会話ログ、将来構想だけの内容は残さない。将来の誤実装を防ぐために必要な判断だけを短く残す。

## View コンパイルキャッシュ

### 判断

View コンパイルキャッシュは標準では有効にしない。

classic / non-worker runtime で必要になった場合だけ、app container から `LatteTemplateRenderer` へ app 別の `cacheDirectory` を明示して有効化する。

### 理由

- 標準 runtime は FrankenPHP worker mode である。
- worker mode では、一度ロードされた template class が同一 PHP process 内に残るため、定常リクエストでディスク上の compiled view cache の効果は小さい。
- View コンパイルキャッシュは HTML response cache ではない。DB row、記事本文、設定、ログイン状態、CSRF token、権限判定結果はキャッシュ対象にしない。
- template 変更の反映は worker restart の問題として扱う。

### 採用しないこと

- `VIEW_CACHE=true` のような env switch は作らない。
- `autoRefresh=false` 相当の設定は標準では採用しない。
- container / config コンパイルキャッシュは現時点では採用しない。

### 有効化する場合

- cache directory は app 別に分ける。
  - `storage/cache/views/front`
  - `storage/cache/views/cms`
- source 変更検知は有効のままにする。
- HTML response cache、DB query cache、CDN cache とは別機能として扱う。

## Layout Global Provider の cache

### 判断

Front の `LayoutGlobalProvider` は、現時点では provider interface や cache decorator へ分けない。

### 理由

- layout global data は Front の Presentation 層が template 用に合成する表示都合の data である。
- Shared に layout cache service を置くと、Front 固有の責務が横断層へ漏れる。
- 現状は `globals()` と `loadGlobals()` を分けるだけで、cache 制御と data 組み立てを読み分けられる。

### 分離を検討する条件

- CMS 更新時の明示 invalidation が必要になる。
- cache key や TTL が複数になる。
- locale、theme、preview、device、user state で layout data が変わる。
- 同じ TTL cache pattern が他の provider / Query に増える。
- test が cache 制御と data 組み立てで読みにくくなる。

## Middleware 境界

### 判断

Middleware は HTTP request / response の入口制御と横断処理に限定する。

対象リソース依存の業務判断は UseCase / Policy / Domain へ寄せる。

### Middleware で扱ってよいこと

- ログイン済みか確認する。
- route metadata の `requires_permission` を確認する。
- CSRF token を検証する。
- IP address、user-agent、request id など HTTP metadata を event に載せる。
- CMS の状態変更 request の HTTP envelope を Audit として記録する。

### Middleware で扱わないこと

- route variables の `{id}` から記事を取得して所有者判定する。
- 記事 status を見て公開、復元、削除の可否を判定する。
- Repository / Query を呼び、resource-level policy を実装する。

### 理由

Middleware に対象リソース依存の判定を置くと、同じ UseCase を CLI、batch、将来の API から呼んだときに認可が抜ける。`writer は自分の記事だけ編集可` のようなルールは、route-level permission ではなく UseCase / Policy で判定する。

## Route metadata

### 判断

現時点では `RouteMetadataReader` や `MatchedRoute` value は作らない。

### 理由

- route metadata を読む箇所がまだ少ない。
- 先に抽象化すると、何のための概念かが半端に見える。
- 現行の route match 2回は理解しやすく、route 数も少ないため性能問題になっていない。

### 導入を検討する条件

- route metadata を読む middleware が増える。
- `Router::ROUTE_METADATA_ATTRIBUTE` 参照が複数箇所へ広がる。
- route 数が増え、match コストが実測で問題になる。
- route name、vars、metadata、controller をまとめた `MatchedRoute` が必要になる。

## Audit / SecurityLog

### 判断

Audit、SecurityLog、HTTP access log は分ける。

CMS の状態変更 HTTP request は `CmsAuditMiddleware` で HTTP envelope として記録する。業務上の before / after や resource id が必要なものは、UseCase / Controller から業務イベントとして明示する。

BusinessAudit という別ログ種別は作らず、既存の `AuditEvent` に集約する。区別は `action`、`resource_type`、`metadata` で表現する。

監査ログは「操作名だけ」では不足する。後から説明・調査できるように、重要な更新系では対象 ID、結果、変更された項目、主要項目の before / after 要約を残す。ただし本文全文、問い合わせ本文、password、CSRF token、cookie、session id、過度な個人情報は残さない。

### 理由

- HTTP request と業務操作は一対一とは限らない。
- middleware は path と status code しか正確に知らない。
- 記事保存、復元、メディア削除、設定更新などは、業務イベントとして明示した方が後から説明しやすい。
- HTTP envelope と業務イベントはどちらも監査証跡であり、保存先や閲覧 UI を分けるほどの運用要件は現時点ではない。
- OWASP Logging Cheat Sheet は、ログの目的に合わせて十分な情報を残し、when / where / who / what、action、object、result などを記録する考え方を示している。一方で sensitive personal data や secrets は除外・mask・sanitize するべきとしている。
- NIST SP 800-92 は、ログ管理の policy / process / infrastructure を整えることを重視している。つまり粒度は実装都合ではなく、後から何を検証したいかで決める。

### 採用しないこと

- 全 GET request を Audit に保存しない。
- CMS login 成功 / 失敗を Audit として保存しない。SecurityLog として扱う。
- request body 全体、password、CSRF token、cookie、session id を保存しない。
- middleware が path や body から resource id、before / after を推測しない。
- `NullAuditLogger` を標準にしない。未設定を黙って捨てると検知できない。
- `BusinessAuditLogger` のような別 logger を作らない。必要になるまでは `AuditEvent` に集約する。
- 変更内容を知るために request body 全体を保存しない。対象 UseCase ごとに必要な項目だけを要約する。

### 次に追加する粒度

- 記事更新: `title`、`slug`、`status`、`category_id`、公開日時、SEO 項目などの before / after。本文は全文ではなく hash、文字数、変更有無に留める。
- 記事作成 / 削除 / 復元: 対象 ID、title、slug、status、復元元 version。
- 設定更新: 変更された setting key と before / after。秘密値が出る設定が増えたら mask 対象にする。
- メディア削除: 対象 ID、file_name、path、mime_type。
- 問い合わせ閲覧や export を追加する場合: 個人情報アクセスとして Audit 対象にする。問い合わせ本文は Audit に複製しない。

## 公開前仕様

### 判断

`robots.txt`、`sitemap.xml`、OGP / Twitter Card、RSS / Atom は場当たり的に実装しない。公開前必須に近いが、先に仕様を決めてから着手する。

問い合わせフォームは当面積極運用しない。ただし外部入力口であるため、運用を開始しない場合でも公開前には最低限の rate limit / spam 対策 / 個人情報取得説明を判断する。直近優先の実装課題ではなく、忘れてはいけない公開前保留項目として扱う。

### 理由

- SEO / SNS / 配信系は一度公開すると外部 crawler や reader に観測されるため、後から雑に変えると挙動が読みにくい。
- 問い合わせフォームは小規模でも spam と個人情報取得の入口になる。

### 採用しないこと

- 仕様未確定のまま OGP / sitemap / feed を仮実装しない。
- 問い合わせフォームを運用しないことを理由に、防御を完全に省略しない。

## 記事本文エディター

### 判断

CMS の記事本文は Markdown、HTML、リッチエディターから選べるようにする。Markdown は捨てない。HTML は既存互換と直接HTMLを書きたい場合のために残す。

リッチエディターは Tiptap の OSS editor を採用する。ただし利用範囲は自前ホストの editor package に限定し、Tiptap Cloud、AI Toolkit、Collaboration、Pro Extensions、有料 Notion-like template は採用しない。Notion-like な `/` コマンドメニューは Tiptap core / StarterKit のデフォルト機能ではなく、公式 docs でも Slash Commands は実験的で未公開 package 扱いのため、この CMS に必要な最小機能だけを自前 UI として実装する。文字列選択時の装飾メニューも CMS 側の自前 UI とし、マウスでの範囲選択中はドラッグを妨げないようにメニューを出さず、mouseup 後に表示する。画像挿入は URL 手入力に寄せず、CMS 登録済み画像を選べる UI を優先する。

保存形式は、選択中の `body_format` に対応する本文ソースを既存の `body_markdown` カラムに保存する。`markdown` は Markdown 文字列、`html` は HTML 文字列、`rich_text` は Tiptap / ProseMirror JSON 文字列を保存する。`body_html` は本文ソースから生成した表示用 HTML の派生 cache として保存し、正本にはしない。公開表示では `body_html` を優先し、未生成の既存行では PHP 側で許可 node / mark だけ HTML 化し、既存の HTMLPurifier を通してから出力する。

CMS の形式切替は保存済みの正本を即時変換する処理ではなく、編集画面内で形式別ドラフトを保持する UI とする。リッチエディターから Markdown / HTML へ切り替えた時は textarea を JSON で上書きしない。選択先のドラフトが空の場合だけ、リッチ本文から Markdown 風テキストまたは HTML へフォールバック変換して表示する。

### 理由

- Markdown 記事資産と運用感を維持できる。
- Tiptap は ProseMirror を直接扱うより UI 実装が薄く済み、Notion-like な編集体験へ寄せやすい。
- `# ` などの Markdown shortcut は Tiptap Heading input rule を使う。`/` メニューは候補表示と node 変換だけを CMS 側で薄く実装する。
- 文字列選択時の装飾メニューは本文編集の中心操作なので、リンククリックでは画面遷移せず、編集用のリンク設定/解除 UI を出す方が自然である。
- Tiptap / ProseMirror 本体は編集状態と command を扱うライブラリであり、CMS の装飾メニュー用アイコンはアプリ側の UI として管理する方が依存を増やさずに済む。
- Tiptap GmbH はドイツ Berlin の法人として Legal Notice を公開しており、OSS editor は MIT license で公開されている。外部 SaaS に本文を預けず、自前ビルド・自前DB保存に限定すれば、運用上のチャイナリスクや vendor SaaS 依存を抑えられる。
- JSON を正本にすると、段落、見出し、リスト、画像、引用、表、区切り線などを構造として扱える。
- 画像挿入はメディア管理の登録済み画像を選ぶ方が、利用者に `/uploads/...` のパスを覚えさせずに済む。
- 表は記事本文の構造として自然に必要になる可能性があり、Tiptap の OSS table extension と Shared renderer の許可 node として扱えば本文モデルを増やさずに済む。
- 表示用 HTML をクライアントから信頼せず、サーバ側 renderer と sanitizer を通す方が XSS 境界が明確になる。
- Tiptap 公式 API も `getJSON()` と `getHTML()` の両方を公開しているため、JSON を編集用の構造化 source、HTML を表示用の派生結果として扱う設計は自然である。
- Front が欲しいのは rich_text 限定 HTML ではなく、本文形式に関係なく表示できる安全な HTML である。そのため列名は `rich_text_html` ではなく `body_html` とする。
- 表示用 HTML を派生 cache として保存すると Front は表示時の JSON parsing / HTML 変換を避けられる。一方で renderer 変更時は再生成が必要になるため、正本はあくまで `body_markdown` と `body_format` とする。

### 採用しないこと

- 本文形式を EAV や汎用フィールド定義エンジンにしない。
- Tiptap Cloud / AI / Collaboration へ接続しない。
- CDN から Tiptap を読み込まない。
- 公式 Slash Commands 実験 extension をそのまま vendor copy しない。
- クライアント生成 HTML だけを正として保存しない。
- `body_html` を編集用 source として扱わない。
- リッチ本文のために公開 front を Node.js rendering へ依存させない。
- 編集用 JSON と表示用 HTML の二重正本を持たない。

### 拡張を検討する条件

- 画像の本文挿入時に、その場でアップロードできる必要が出る。
- Markdown から rich_text への明示変換が必要になる。
- リッチ本文の検索品質を上げるため、plain text cache を別カラムへ持つ。
- renderer の許可 node / mark を変更した場合、既存 `body_html` の再生成 command を用意する。
- 記事履歴差分で JSON の node 単位比較が必要になる。

## Application Port

### 判断

Port は最初の選択肢ではなく、Application が外側の能力を本当に必要とし、他の配置では依存方向や責務が崩れる場合に使う。

詳細は `docs/application_ports.md` を正とする。

### 例

- password verification は Application が必要とする外側の暗号処理能力なので Port 化してよい。
- Logger は PSR-3 を shared / framework の横断基盤として使えば足りる間は、Application Port 化しない。
- Audit も通常は shared / framework / presentation 側の横断基盤でよい。業務成立条件として証跡記録が必要になった場合だけ、業務名を持つ Port を検討する。

## Commit 粒度

### 判断

実装作業の commit は、意味のある作業単位に分ける。特に変更対象の境界が異なる場合は、同じリファクタリングテーマでも commit を分ける。

基本の分け方:

- Framework の変更
- Kernel の変更
- Shared の変更
- `apps/cms` の変更
- `apps/front` の変更
- docs / tests の追随

docs / tests は、対応する実装 commit に含める方が追いやすい場合は同じ commit に入れてよい。複数境界にまたがる純粋な機械的変更は、境界ごとに commit を分ける。

### 理由

- 依存境界ごとに review しやすくする。
- revert するときに Framework / Kernel / Shared / app の影響範囲を分離できるようにする。
- 「同じ目的」でも責務の違う層をまとめると、後から変更理由を追いにくくなる。

### 採用しないこと

- 1行ごとの過度に細かい commit。
- Framework、Kernel、Shared、app の変更を、単に同じリファクタリング名だけで一つにまとめること。
