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

`sites` は、マルチサイト対応のためだけの将来拡張ではなく、1 サイト運用でも意味を持つ基礎概念として扱う。`sites` は公開面に関する大元のテーブルであり、表示設定、外部連携、固定ページ、トップページ設定は `sites` を起点にする。

ただし、全テーブルへ機械的に `site_id` をばらまかない。`site_id` を持つのは、site に属する意味が明確なものだけにする。

### 持つべき概念

最初の実装で持つ概念は次に絞る。

```text
sites
site_settings
site_integrations
pages.site_id
pages.visibility
```

`sites` は公開面そのものを表す。

`site_settings` はサイト名、description、theme、canonical base URL、OGP 画像、トップページを記事一覧にするか固定ページ/LPにするかなど、公開表示に関係する設定を持つ。

`site_integrations` は Slack、Google Search Console、Cloudflare、GA4 / GTM、Mailgun、Webhook など外部サービスとの接続設定を持つ。これは表示設定ではないため、`site_settings.settings` のような何でも箱には入れない。

`pages.site_id` は、固定ページがどの site に属するかを表す。固定ページは記事よりも site の構造に近く、会社概要、問い合わせ、プライバシーポリシー、LP などは「どの site のページか」を直接持つ方が自然である。

`pages.visibility` は、公開中の固定ページをどの経路で見せてよいかを表す。`status` は draft / published / trashed の公開状態、`visibility` は published の先にある見せ方であり、同じものではない。

次の概念は、最初の実装では入れない。

```text
articles.site_id
article_site_assignments
page_placements
site_navigation_items
```

`articles.site_id` は、記事が必ず 1 site だけに属する設計を選ぶ場合に入れる。現時点では、既存の記事を `/` と `/business` で別の見せ方にする試作が成立しており、今回の実装目的を満たすために記事の site 所属を決める必要がない。ここで `articles.site_id` を入れると、カテゴリ、タグ、関連記事、検索、sitemap、既存 front / business front の振る舞いまで同時に決める必要が出る。

これは「記事の site 所属を固定しないこと」を設計条件にするという意味ではない。現時点では、記事所属を固定しなくても目的を満たせるだけである。将来、記事は 1 site 所属でよいと決められるなら `articles.site_id` を入れる方が単純でよい。同一の記事 ID を複数 site に公開したい要求が明確なら、`articles.site_id` ではなく `article_site_assignments` のような関連テーブルを検討する。今回の初期実装では、どちらの判断も妨げない形に留める。

`page_placements` は、site のテンプレートが `home` や `contact_success_body` のような用途名を知っており、その用途に固定ページ本文を差し込む必要が出た場合にだけ検討する。これはページ単位ではなく用途単位の結合であり、テンプレートとの契約が強い。トップページだけを固定ページにする段階では、`site_settings.home_page_id` の方が読みやすい。

`site_navigation_items` は、固定ページ以外のリンク、外部 URL、カテゴリリンク、ヘッダー/フッター別の表示、ラベル差し替えなど、navigation を CMS で明示管理したくなった場合に入れる。公開中固定ページを `display_order` 順に出すだけなら、`pages.site_id` と `pages.visibility` で足りる。

### 責務境界

この model では、似ている概念を次のように分ける。

```text
apps/front, apps/front-business
  = code / template / route の実装境界

sites
  = 公開面の単位

articles, pages
  = コンテンツ本体

pages.site_id
  = 固定ページが属する site

pages.visibility
  = 固定ページ自体をどの経路で見せてよいか

site_settings
  = site の表示方針と公開設定

site_integrations
  = site に属する外部接続
```

`apps/front` や `apps/front-business` は PHP code の境界であり、`site` そのものではない。1つの front app が1つの site だけを担当してもよいし、将来 routing / host / base path で site を解決して複数 site を担当してもよい。ただし Kernel が特定 site の設定を読む形にはしない。

`articles` は、今回の初期実装では site 所属を持たない。記事を site 固有にするか、同一記事を複数 site に公開できる共有コンテンツにするかは、今回の目的を満たすうえでまだ決めなくてよい。ここで重要なのは「固定しないこと」ではなく、不要な決定を先に schema へ入れないことである。将来 `articles.site_id` を入れる判断が自然にできるよう、記事 query や front app 側の条件を過度に共有コンテンツ前提へ寄せない。

`pages` は、今回の初期実装で site 所属を持つ。固定ページは site の構造に近く、同じ本文を複数 site に出したい場合でも、最初は同内容の page を site ごとに複製する方が分かりやすい。固定ページを共有コンテンツとして扱いたい要求が出るまでは、関連テーブルで抽象化しない。

`site_settings` は「トップページを記事一覧にするか固定ページにするか」のような方針を持つ。トップページが固定ページの場合に限り、`home_page_id` を持つ。これは site のトップ表示設定そのものであり、一般的な固定ページ配置とは分けて扱う。

`site_integrations` は外部連携の所属先を持つ。credential や webhook を `site_settings` に混ぜると、表示設定と接続設定が同じ経路に乗ってしまうため分ける。

`pages.visibility` は page 自体の表示経路である。site ごとの所属は `pages.site_id`、その page を通常 URL で見せるか、導線から隠すかは `pages.visibility` で決める。

### トップページの扱い

トップページに何を表示するかという方針は `site_settings` が持つ。

```text
site_settings.home_mode
- article_index
- page
```

`article_index` は従来どおり記事一覧をトップにする。`page` は固定ページまたは LP をトップにする。

`home_mode = page` の場合に、実際にどの固定ページをトップへ置くかは `site_settings.home_page_id` が持つ。

```text
site_settings
- site_id
- home_mode = page
- home_page_id
```

`home_page_id` は、トップページの表示方針と一体の設定として扱う。`privacy_policy_page_id`、`terms_page_id`、`contact_success_page_id` のような用途別 page ID が増え始めたら、その時点で `page_placements` のような用途別配置テーブルを再検討する。トップページ 1 件だけの段階で `page_placements` を入れると、用途名とテンプレートの結合だけが先に増えて分かりづらい。

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

`placement_only` は、`/pages/{slug}` の通常ルートでは表示せず、トップページなど明示的な参照経由でだけ表示する状態を表す。LP を site のトップに置く、といった用途に使う。

複数 site では、`listed` であっても最終的にどの site に出るかは `pages.site_id` を見る。`listed` は「その site 内で出してよい表示経路」を表すだけで、全 site へ出すという意味ではない。

`site_settings.home_page_id` は `pages.visibility` の代わりではない。`visibility` はページ自身の見せ方、`home_page_id` は site のトップとして使う page の参照である。

### page_placements を初期実装に入れない理由

`page_placements` は、通常の固定ページ一覧や navigation を作るためのものではない。site のコードやテンプレートが「この場所には固定ページ本文を使う」と明示的に知っている場合に使う、用途単位の配置表である。

代表例は次のようなものになる。

```text
home
contact_success_body
privacy_policy
terms
footer_profile
campaign_landing
```

これはテンプレートと密結合になる。密結合自体が悪いのではなく、テンプレートがその用途を知っている以上、結合は実際に存在している。しかし、トップページだけを固定ページにする段階では、`home` という用途名を別テーブルに切り出すより、`site_settings.home_mode` と `site_settings.home_page_id` の方が意味を追いやすい。

そのため、初期実装では `page_placements` を入れない。問い合わせ完了本文、フッター紹介文、キャンペーン枠など、複数の用途別 page 参照が増え、`site_settings` に page ID が増殖し始めた時点で導入を再検討する。

`page_placements` を導入する場合でも、これは「意味のある slot への割り当て」であり、「リンク一覧」ではない。ヘッダーやフッターにどのリンクをどの順番で出すかを CMS で明示管理したくなった場合は、別途 `site_navigation_items` を検討する。

### 避けること

次の設計は採用しない。

- `slug = business-home` のような暗黙ルール。
- `pages.is_business_home` のような app 固有フラグ。
- `site_settings` に用途別 page ID を無制限に増やすこと。トップページ用の `home_page_id` は例外として扱う。
- `pages.auto_display` のように、何に自動表示するのか分からない曖昧なフラグ。
- `settings` JSON に外部連携、配置、表示設定をすべて詰めること。
- 全テーブルに `site_id = 1` を機械的に追加すること。
- 今回の目的に不要な段階で `articles.site_id` を追加し、記事の公開所属を先に決めてしまうこと。
- 初期実装で汎用的な `content_site_assignments` を追加し、使わない抽象を先に作ること。
- site ごとに `articles_1`、`articles_2` のような同型テーブルを増やすこと。
- Kernel が特定 front app の base path や site 設定を読むこと。

site ごとの同型テーブル分割は、完全独立 tenant、DB 分離、障害分離、顧客ごとの物理削除が中心のマルチテナント SaaS なら選択肢になり得る。しかしこの CMS の目的は、同じ管理者が複数の公開面を安全に運用することであり、テーブル分割ではなく共通テーブル + site 関連で表現する。

## できるようになること

`sites` を導入すると、次のことを明示的に扱える。

- 1 つの CMS で企業サイト、採用サイト、オウンドメディア、LP を分ける。
- site ごとにトップページを記事一覧にするか固定ページ/LPにするかを選ぶ。
- site ごとにトップページ用の固定ページを割り当てる。
- site ごとに固定ページを分ける。
- site ごとに canonical base URL を変える。
- site ごとに sitemap の対象を変える。
- site ごとに theme / layout を変える。
- site ごとに問い合わせ導線や外部連携を変える。
- 固定ページを「本文」「所属 site」「表示経路」「トップページ設定」に分けて扱う。
- business 用 front app を増やしても、通常 front へ固定ページや navigation が漏れない。

記事を site ごとに分けること、同じ記事を複数 site に出すこと、navigation を完全に CMS 管理することは、初期実装では扱わない。必要になった時点で `articles.site_id`、`article_site_assignments`、`site_navigation_items` のいずれが適切かを個別に判断する。初期実装では、記事を共有コンテンツとして扱うことを条件にしない。

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
   - `home_mode = page` の場合の参照として `home_page_id` を持たせる。
   - front / cms の参照箇所を一気に壊さず、互換期間を設けるか、同一 commit で安全に置き換える。

3. `site_integrations` を追加する。
   - 外部連携設定を `site_settings` に混ぜないための受け皿を作る。
   - secret を扱う場合は通常設定と同じ表示・監査・出力経路に乗せない。

4. `pages.site_id` と `pages.visibility` を追加する。
   - 固定ページは site の構造に近いため、関連テーブルではなく直接 `site_id` を持たせる。
   - `status` と混同しない。
   - 最初の値は `listed` / `unlisted` / `placement_only` を候補にする。
   - 既存の固定ページは、現行挙動維持のため原則 `listed` へ移行する。
   - 既存の固定ページは default site 所属へ移行する。

5. 記事の site 所属はまだ schema に入れない。
   - `apps/front-business` のような別表示は、当面は front app 側の query 条件で扱う。
   - これは記事を共有コンテンツに固定するためではなく、今回の目的に不要な決定を避けるためである。
   - 記事を 1 site 所属にする必要が出たら `articles.site_id` を検討する。
   - 同一記事 ID を複数 site に出す必要が出たら `article_site_assignments` を検討する。

6. `page_placements` と `site_navigation_items` は初期実装に入れない。
   - トップページだけなら `site_settings.home_page_id` で扱う。
   - 用途別 page 参照が増えたら `page_placements` を検討する。
   - 固定ページ以外の navigation 管理が必要になったら `site_navigation_items` を検討する。

## 判断保留

次の点は実装前に決める。

- `site_key` の値を `front`, `business` のような app 寄りにするか、`main`, `business_service` のような運用寄りにするか。
- `site_settings` を key-value のまま site 所属にするか、主要項目だけ typed column にするか。
- `site_integrations` の secret を DB に暗号化して持つか、env / secret manager 参照にするか。
- 記事を将来 `articles.site_id` で 1 site 所属にするか、`article_site_assignments` で複数 site 公開を許すか。
- 固定ページに `page_kind = normal / landing` のような種類を持たせるか。現時点では `visibility = placement_only` と `site_settings.home_page_id` で LP 用途は表現できるため、本文種別としての `page_kind` は必須ではない。
- `pages.visibility` の値を `listed` / `unlisted` / `placement_only` で足りると見るか、navigation と sitemap を別軸に分けるか。
- `home_mode` の値を `article_index` / `page` の 2 値にするか、将来の search / category / custom controller を見越して拡張可能な値にするか。
- 用途別 page 参照が増えた場合に `page_placements` を入れるか、それぞれ typed column として扱うか。
- navigation を `pages.display_order` の簡易導線に留めるか、`site_navigation_items` で明示管理するか。
- 既存 `apps/front-business` を site model の試験対象にするか、別システム作成時の設計にだけ反映するか。

## 当面の結論

現時点で `apps/front-business` を作るだけで済む範囲なら、マルチサイト CMS として格上げしない。カテゴリ絞り込みや別テンプレートだけで成立する front app は、既存 CMS に干渉しない別表示として扱う。

ただし、CMS 上で front ごとのトップページ、固定ページ、sitemap、canonical、外部連携を管理したくなった時点で、slug 特別扱いや app 固有フラグではなく、`sites` を中核にした site model へ格上げする。

navigation は、固定ページを site ごとに分けて `display_order` 順に出すだけなら `pages.site_id` で足りる。外部リンク、カテゴリリンク、任意ラベル、ヘッダー/フッター別配置などを CMS で扱いたくなった時点で、`site_navigation_items` を追加する。
