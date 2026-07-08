# Site Model Plan

## 目的

CMS に `sites` 概念を追加する主目的は、複数の公開面を後から扱えるようにすることだけではなく、現在 1 サイトしかない場合でも「公開面の単位」を明示することである。

一方で、この機能を検討し始めた発端は、別ドメインや別ルート URI を持つ複数サイトを 1 つの CMS から公開できるようにしたい、という要求である。そのため、最初の実装では過剰なマルチサイト機能を入れないが、複数サイト公開へ進めるときに構造を壊さず拡張できることは前提にする。

ここでいう `site` は、記事や固定ページそのものではなく、次のような公開上のまとまりを表す。

- canonical base URL
- sitemap の対象範囲
- トップページの表示方針
- 固定ページの配置
- navigation
- theme / layout
- サイト名、説明、OGP などの公開設定
- 問い合わせ導線
- 外部連携の所属先

`site` を持つことで、コンテンツ本体、表示場所、公開設定、外部連携を混ぜずに扱えるようにする。

## 経緯

`apps/front-business` を追加し、既存 CMS の記事を `/business` で別の見せ方にする試作を行った。この範囲では、CMS のデータモデルに手を入れず、front app 側でカテゴリを絞り込むだけで成立した。

一方で、次の要望を考えると違和感が出た。

- `/business` のトップページを CMS から編集したい。
- 固定ページ機能で LP を作り、それをドメインルートや `/business` に置きたい。
- 通常 front 用の固定ページを business 側には混ぜたくない。
- business 用固定ページや navigation を通常 front へ漏らしたくない。
- sitemap / canonical / page links を front ごとに分けたい。

この問題は、固定ページ機能の不足だけではない。現在の CMS は、公開面が 1 つである前提のまま、front app だけを複数に増やしている。そのため「このコンテンツはどの site に出るのか」「この固定ページはどこに配置されるのか」という概念が存在しない。

`slug = business-home` のような特別扱いは早いが、暗黙ルールになる。後から見たときに、なぜその slug だけがトップなのか、どの site の都合なのかが分からなくなるため採用しない。

また、ビジネスサイトだけを作るなら、現行システムをコピーして別システムとして作る方が軽い。ただしこの会話自体が、CMS 内に `site` という公開面の単位がないために「コピーで分ける」判断へ寄っていることを示している。

## 方針

`sites` は、マルチサイト対応のためだけの将来拡張ではなく、1 サイト運用でも意味を持つ基礎概念として扱う。`sites` は公開面に関する大元のテーブルであり、表示設定、外部連携、navigation、固定ページ配置、コンテンツ公開先は `sites` を起点にする。

ただし、全テーブルへ機械的に `site_id` をばらまかない。`site_id` を持つのは、site に属する意味が明確なものだけにする。

### 持つべき概念

```text
sites
site_settings
site_integrations
pages.visibility
page_placements
content_site_assignments
site_navigation_items
```

`sites` は公開面そのものを表す。

`site_settings` はサイト名、description、theme、canonical base URL、OGP 画像、トップページを記事一覧にするか固定ページ/LPにするかなど、公開表示に関係する設定を持つ。

`site_integrations` は Slack、Google Search Console、Cloudflare、GA4 / GTM、Mailgun、Webhook など外部サービスとの接続設定を持つ。これは表示設定ではないため、`site_settings.settings` のような何でも箱には入れない。

`pages.visibility` は、公開中の固定ページをどの経路で見せてよいかを表す。`status` は draft / published / trashed の公開状態、`visibility` は published の先にある見せ方であり、同じものではない。

`page_placements` は「この site のこの特別な場所に、この固定ページを置く」という明示的な配置を表す。たとえば `site_key = business`, `placement_key = home`, `page_id = 123` のように扱う。

`content_site_assignments` は記事や固定ページなどのコンテンツを、どの site の公開対象にするかを表す。コンテンツ本体と公開先を分けるための概念である。

`site_navigation_items` は site ごとの navigation を表す。公開中固定ページを自動で全部 navigation に出す方式だけでは、複数 site の運用に耐えない。

### 責務境界

この model では、似ている概念を次のように分ける。

```text
apps/front, apps/front-business
  = code / template / route の実装境界

sites
  = 公開面の単位

articles, pages
  = コンテンツ本体

content_site_assignments
  = コンテンツをどの site の公開対象にするか

pages.visibility
  = 固定ページ自体をどの経路で見せてよいか

site_settings
  = site の表示方針と公開設定

page_placements
  = site 上の意味ある slot に固定ページを割り当てる

site_navigation_items
  = site 上に表示するリンクと順序

site_integrations
  = site に属する外部接続
```

`apps/front` や `apps/front-business` は PHP code の境界であり、`site` そのものではない。1つの front app が1つの site だけを担当してもよいし、将来 routing / host / base path で site を解決して複数 site を担当してもよい。ただし Kernel が特定 site の設定を読む形にはしない。

`site_settings` は「トップページを記事一覧にするか固定ページにするか」のような方針を持つが、固定ページ ID の羅列を持たない。外部連携の credential や webhook も持たない。

`page_placements` は route 定義でも navigation でもない。`home`、`contact_success_body`、`privacy_policy` のように、アプリケーションが意味を知っている slot に page を割り当てるための表である。

`site_navigation_items` は、ユーザーに見せるリンクの表である。リンク先が固定ページでも記事でも外部URLでもよい。表示順、ラベル、表示場所は navigation の責務であり、`page_placements` に持たせない。

`content_site_assignments` は公開対象の scope であり、表示場所ではない。ある page が site に割り当てられていても、それだけで home や footer に出るわけではない。

`pages.visibility` は page 自体の表示経路であり、site 固有の公開先ではない。site ごとの公開先は `content_site_assignments` で決める。

### トップページの扱い

トップページに何を表示するかという方針は `site_settings` が持つ。

```text
site_settings.home_mode
- article_index
- page
```

`article_index` は従来どおり記事一覧をトップにする。`page` は固定ページまたは LP をトップにする。

`home_mode = page` の場合に、実際にどの固定ページをトップへ置くかは `page_placements` が持つ。

```text
page_placements
- site_id
- placement_key = home
- page_id
```

`site_settings.home_page_id` のように直接 page ID を持たせると、`privacy_policy_page_id`、`terms_page_id`、`contact_success_page_id` のように配置情報が settings へ増殖しやすい。そのため、`site_settings` は表示方針、`page_placements` は固定ページの割り当てとして分ける。

### 固定ページの表示経路

固定ページは、公開状態と表示経路を分けて扱う。

```text
pages.status
- draft
- published
- trashed

pages.visibility
- listed
- unlisted
- placement_only
```

`listed` は、`/pages/{slug}` で直接表示でき、固定ページ一覧や自動 navigation 候補にしてよい状態を表す。

`unlisted` は、`/pages/{slug}` で直接表示できるが、固定ページ一覧や自動 navigation 候補には出さない状態を表す。公開 URL は持つが、能動的に導線を出さないページに使う。

`placement_only` は、`/pages/{slug}` の通常ルートでは表示せず、`page_placements` など明示的な配置経由でだけ表示する状態を表す。LP を site のトップに置く、問い合わせ完了画面に固定ページ本文を差し込む、といった用途に使う。

複数 site では、`listed` であっても最終的にどの site に出るかは `content_site_assignments` を見る。`listed` は「出してよい表示経路」を表すだけで、全 site へ出すという意味ではない。

`page_placements` は `pages.visibility` の代わりではない。`visibility` はページ自身の見せ方、`page_placements` は site 上の特別な場所への割り当てである。

### page_placements の用途

`page_placements` は、通常の固定ページ一覧や navigation を作るためのものではない。site のコードやテンプレートが「この場所には固定ページ本文を使う」と明示的に知っている場合に使う。

代表例:

```text
home
contact_success_body
privacy_policy
terms
footer_profile
campaign_landing
```

`home` は site のトップページに置く固定ページを表す。`contact_success_body` は問い合わせ完了画面に差し込む説明本文を表す。`privacy_policy` や `terms` は、法務上またはテンプレート上、意味を持つページ参照として扱う。

一方で、ヘッダーやフッターにどのリンクをどの順番で出すかは `site_navigation_items` が持つ。`page_placements` は「意味のあるslotへの割り当て」であり、「リンク一覧」ではない。

### 避けること

次の設計は採用しない。

- `slug = business-home` のような暗黙ルール。
- `pages.is_business_home` のような app 固有フラグ。
- `site_settings.home_page_id` のように、固定ページ配置を settings に直接増やすこと。
- `pages.auto_display` のように、何に自動表示するのか分からない曖昧なフラグ。
- `settings` JSON に外部連携、配置、表示設定をすべて詰めること。
- 全テーブルに `site_id = 1` を機械的に追加すること。
- site ごとに `articles_1`、`articles_2` のような同型テーブルを増やすこと。
- Kernel が特定 front app の base path や site 設定を読むこと。

site ごとの同型テーブル分割は、完全独立 tenant、DB 分離、障害分離、顧客ごとの物理削除が中心のマルチテナント SaaS なら選択肢になり得る。しかしこの CMS の目的は、同じ管理者が複数の公開面を安全に運用することであり、テーブル分割ではなく共通テーブル + site 関連で表現する。

## できるようになること

`sites` を導入すると、次のことを明示的に扱える。

- 1 つの CMS で企業サイト、採用サイト、オウンドメディア、LP を分ける。
- site ごとにトップページを記事一覧にするか固定ページ/LPにするかを選ぶ。
- site ごとにトップページ用の固定ページを割り当てる。
- site ごとに navigation を変える。
- site ごとに canonical base URL を変える。
- site ごとに sitemap の対象を変える。
- site ごとに theme / layout を変える。
- site ごとに問い合わせ導線や外部連携を変える。
- 同じ記事を複数 site に出すか、特定 site のみに出すかを明示する。
- 固定ページを「本文」「表示経路」「配置」に分けて扱う。
- business 用 front app を増やしても、通常 front へ固定ページや navigation が漏れない。

## 実装方針

最初の実装では、既存 `apps/front` への影響を最小にする。現行挙動を変えるためではなく、site 概念を導入してから段階的に参照先を移す。

1. `sites` を追加する。
   - 初期 site を 1 件 seed する。
   - `site_key` は immutable な識別子として扱う。
   - 表示名は変更可能にする。

2. `site_settings` を site 所属に寄せる。
   - 既存 `setting_key` / `setting_value` をすぐ壊さない。
   - migration では既存設定を default site に所属させる。
   - トップページ方針として `home_mode = article_index | page` を持たせる。
   - front / cms の参照箇所を一気に壊さず、互換期間を設けるか、同一 commit で安全に置き換える。

3. `site_integrations` を追加する。
   - 外部連携設定を `site_settings` に混ぜないための受け皿を作る。
   - secret を扱う場合は通常設定と同じ表示・監査・出力経路に乗せない。

4. `pages.visibility` を追加する。
   - `status` と混同しない。
   - 最初の値は `listed` / `unlisted` / `placement_only` を候補にする。
   - 既存の固定ページは、現行挙動維持のため原則 `listed` へ移行する。

5. `page_placements` を追加する。
   - `home` のような placement key を明示する。
   - `/` や `/business` が固定ページをトップとして読む場合は、slug ではなく placement を見る。
   - 固定ページ本体は `pages` のままにする。
   - navigation の代替にしない。リンク表示は `site_navigation_items` で扱う。

6. `content_site_assignments` を追加する。
   - 記事、固定ページ、カテゴリ、タグのどこまで site assignment を持たせるかは段階的に決める。
   - 最初から全コンテンツへ必須化しない。

7. `site_navigation_items` を追加する。
   - 既存の「公開中固定ページを display_order 順に出す」方式は単一 site 用の簡易実装として扱う。
   - 複数 site では navigation を配置として明示する。

## 判断保留

次の点は実装前に決める。

- `site_key` の値を `front`, `business` のような app 寄りにするか、`main`, `business_service` のような運用寄りにするか。
- `site_settings` を key-value のまま site 所属にするか、主要項目だけ typed column にするか。
- `site_integrations` の secret を DB に暗号化して持つか、env / secret manager 参照にするか。
- `content_site_assignments` を記事だけ先に入れるか、固定ページまで同時に入れるか。
- 固定ページに `page_kind = normal / landing` のような種類を持たせるか。現時点では `visibility = placement_only` と `page_placements.home` だけで LP 用途は表現できるため、本文種別としての `page_kind` は必須ではない。
- `pages.visibility` の値を `listed` / `unlisted` / `placement_only` で足りると見るか、navigation と sitemap を別軸に分けるか。
- `home_mode` の値を `article_index` / `page` の 2 値にするか、将来の search / category / custom controller を見越して拡張可能な値にするか。
- 既存 `apps/front-business` を site model の試験対象にするか、別システム作成時の設計にだけ反映するか。

## 当面の結論

現時点で `apps/front-business` を作るだけで済む範囲なら、マルチサイト CMS として格上げしない。カテゴリ絞り込みや別テンプレートだけで成立する front app は、既存 CMS に干渉しない別表示として扱う。

ただし、CMS 上で front ごとのトップページ、固定ページ、navigation、sitemap、canonical、外部連携を管理したくなった時点で、slug 特別扱いや app 固有フラグではなく、`sites` を中核にした site model へ格上げする。
