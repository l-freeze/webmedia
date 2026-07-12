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

`site_settings` は typed table として、サイト名、description、theme、hero など現行公開表示に関係する設定を持つ。`setting_key` / `setting_value` の EAV 形式にはしない。現行システムでは単一サイト前提のため `site_id` を持たせず、multi-site 化する段階で site 所属を追加する。旧 EAV 形式の `site_settings` は migration / seed にも残さない。

`site_integrations` は Slack、Google Search Console、Cloudflare、GA4 / GTM、Mailgun、Webhook など外部サービスとの接続設定を持つ。これは表示設定ではないため、`site_settings.settings` のような何でも箱には入れない。

`pages.site_id` は、固定ページがどの site に属するかを表す。固定ページは記事よりも site の構造に近く、会社概要、問い合わせ、プライバシーポリシー、LP などは「どの site のページか」を直接持つ方が自然である。

`pages.visibility` は、公開中の固定ページをどの経路で見せてよいかを表す。`status` は draft / published / trashed の公開状態、`visibility` は published の先にある見せ方であり、同じものではない。

次の概念は、最初の実装では入れない。

```text
articles.site_id
article_site_publications
page_placements
site_navigation_items
```

`articles.site_id` は、記事が必ず 1 site だけに属する設計を選ぶ場合に入れる。現時点では、既存の記事を `/` と `/business` で別の見せ方にする試作が成立しており、今回の実装目的を満たすために記事の site 所属を決める必要がない。ここで `articles.site_id` を入れると、カテゴリ、タグ、関連記事、検索、sitemap、既存 front / business front の振る舞いまで同時に決める必要が出る。

これは「記事の site 所属を固定しないこと」を設計条件にするという意味ではない。現時点では、記事所属を固定しなくても目的を満たせるだけである。将来、記事は 1 site 所属でよいと決められるなら `articles.site_id` を入れる方が単純でよい。同一の記事 ID を複数 site に公開したい要求が明確なら、`articles.site_id` ではなく `article_site_publications` のような公開先テーブルを検討する。今回の初期実装では、どちらの判断も妨げない形に留める。

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
  = 現行単一サイトの表示方針と公開設定

site_integrations
  = site に属する外部接続
```

`apps/front` や `apps/front-business` は PHP code の境界であり、`site` そのものではない。1つの front app が1つの site だけを担当してもよいし、将来 routing / host / base path で site を解決して複数 site を担当してもよい。ただし Kernel が特定 site の設定を読む形にはしない。

`articles` は、今回の初期実装では site 所属を持たない。記事を site 固有にするか、同一記事を複数 site に公開できる共有コンテンツにするかは、今回の目的を満たすうえでまだ決めなくてよい。ここで重要なのは「固定しないこと」ではなく、不要な決定を先に schema へ入れないことである。将来 `articles.site_id` を入れる判断が自然にできるよう、記事 query や front app 側の条件を過度に共有コンテンツ前提へ寄せない。

`pages` は、今回の初期実装で site 所属を持つ。固定ページは site の構造に近く、同じ本文を複数 site に出したい場合でも、最初は同内容の page を site ごとに複製する方が分かりやすい。固定ページを共有コンテンツとして扱いたい要求が出るまでは、関連テーブルで抽象化しない。

multi-site 化後の `site_settings` は「トップページを記事一覧にするか固定ページにするか」のような方針を持つ。トップページが固定ページの場合に限り、`home_page_id` を持つ。これは site のトップ表示設定そのものであり、一般的な固定ページ配置とは分けて扱う。現行の単一サイト用 `site_settings` には、まだ `home_mode` / `home_page_id` を入れない。

`site_integrations` は外部連携の所属先を持つ。credential や webhook を `site_settings` に混ぜると、表示設定と接続設定が同じ経路に乗ってしまうため分ける。

`pages.visibility` は page 自体の表示経路である。site ごとの所属は `pages.site_id`、その page を通常 URL で見せるか、導線から隠すかは `pages.visibility` で決める。

### トップページの扱い

multi-site 化後、トップページに何を表示するかという方針は `site_settings` が持つ。

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

記事を site ごとに分けること、同じ記事を複数 site に出すこと、navigation を完全に CMS 管理することは、初期実装では扱わない。必要になった時点で `articles.site_id`、`article_site_publications`、`site_navigation_items` のいずれが適切かを個別に判断する。初期実装では、記事を共有コンテンツとして扱うことを条件にしない。

## 実装方針

最初の実装では、既存 `apps/front` への影響を最小にする。現行挙動を変えるためではなく、site 概念を導入してから段階的に参照先を移す。

ただし、旧 `site_settings` の EAV 解消は site model 格上げとは独立した先行修正として扱う。現行システムでは単一サイト前提の typed `site_settings` を作り、旧 EAV 形式は migration / seed からも除去する。`site_id`、`sites`、`pages.site_id` はまだ入れない。multi-site 化へ進む段階で、ここに site 所属を追加する。

1. `sites` を追加する。
   - 初期 site を 1 件 seed する。
   - `site_key` は immutable な識別子として扱う。
   - 表示名は変更可能にする。

2. 現行システム用の `site_settings` を typed table として作る。
   - `setting_key` / `setting_value` の EAV 形式にしない。
   - 旧 EAV 形式の `site_settings` は migration / seed にも残さない。
   - 現行システムは単一サイトなので `site_id` を持たせない。
   - migration では既存設定を typed row として seed する。
   - `site_name`、`site_description`、`contact_email`、`front_theme`、`search_enabled`、`hero_image`、`hero_title`、`hero_description` を列として持つ。
   - `home_mode`、`home_page_id`、`canonical_base_url` は multi-site / LP 配置へ進む段階まで入れない。
   - front / cms の参照箇所は EAV ではなく typed `site_settings` を読む。

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
   - 同一記事 ID を複数 site に出す必要が出たら `article_site_publications` を検討する。

6. `page_placements` と `site_navigation_items` は初期実装に入れない。
   - トップページだけなら `site_settings.home_page_id` で扱う。
   - 用途別 page 参照が増えたら `page_placements` を検討する。
   - 固定ページ以外の navigation 管理が必要になったら `site_navigation_items` を検討する。

## 判断保留

次の点は実装前に決める。

- `site_key` の値を `front`, `business` のような app 寄りにするか、`main`, `business_service` のような運用寄りにするか。
- `site_integrations` の secret を DB に暗号化して持つか、env / secret manager 参照にするか。
- 記事を将来 `articles.site_id` で 1 site 所属にするか、`article_site_publications` で複数 site 公開を許すか。
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

## 備考: 記事の公開先を site ごとに分ける場合

記事をどの site で公開するかを明示的に扱うなら、`articles.site_id` ではなく `article_site_publications` のような公開先テーブルを追加する選択肢がある。

```text
articles
  = 記事本体

sites
  = 公開面

article_site_publications
  = この記事をどの site で公開対象にするか
```

この設計でできるようになることは次の通り。

- 同じ記事本体を、複数 site の公開対象にできる。
- site ごとに記事一覧、記事詳細、sitemap、canonical の対象範囲を分けられる。
- site ごとに公開開始、公開停止、一覧表示有無を制御できる。
- site ごとに title / description / canonical 指定など、公開上の metadata を差し替えられる。
- `apps/front-business` のような別 front が、カテゴリ絞り込みだけではなく CMS 上の公開先設定を見て記事を出せる。

ただし、これは単なる汎用化ではなく重い設計判断である。`article_site_publications` を入れると、記事の取得、一覧、詳細、sitemap、canonical、検索、関連記事、CMS 編集画面、監査、権限に「記事本体」と「site への公開」という二層構造が入る。

そのため、導入条件は次のいずれかが明確になった場合に限る。

- 同一 article ID を複数 site に正式公開したい。
- site ごとに公開/非公開や公開期間を変えたい。
- site ごとに canonical や sitemap 対象を明示管理したい。
- 記事本体は共通だが、公開面ごとの title / description / 導線を変えたい。
- 記事を複製して運用すると、編集漏れや更新差分の管理コストが実際に問題になる。

逆に、次の場合は導入しない。

- 記事は基本的に 1 site 所属でよい。
- 同じ内容を別 site に出す頻度が低く、記事複製で十分に管理できる。
- 現時点の要求が、front app 側の見た目変更やカテゴリ絞り込みだけで成立する。
- 「後で便利そう」という理由だけで公開先を抽象化したい。

実装する場合の最小 schema は次のようにする。

```text
article_site_publications
- article_id
- site_id
- is_canonical
- created_at
- updated_at
```

必要になった時点で、次の列を追加候補にする。

```text
- visibility
- published_at
- unpublished_at
- site_title
- site_description
```

`visibility` は site 内で記事一覧や sitemap に出すかを表す。`published_at` / `unpublished_at` は site ごとの公開期間を表す。`site_title` / `site_description` は記事本体の title / description を壊さず、公開面ごとの検索表示や SNS 表示を調整するために使う。

管理画面に追加する機能は、既存の記事編集画面へ無秩序に項目を混ぜない。記事本文の編集と site 公開設定は関心が違うため、次のどちらかで扱う。

```text
記事編集画面内の「公開先」タブ
  - 記事本文と同じ画面内だが、編集領域を明確に分ける

記事詳細配下の「公開先管理」画面
  - /admin/articles/{id}/publications のように分ける
```

管理画面で必要になる操作は次の通り。

- 記事を公開する site を追加する。
- 記事を site の公開対象から外す。
- canonical とする site を選ぶ。
- site ごとの一覧表示 / sitemap 対象を切り替える。
- site ごとの公開開始日時 / 公開終了日時を設定する。
- site ごとの title / description を必要に応じて設定する。

新たに必要な管理機能は次の通り。

- site 一覧と site 設定管理。
- 記事ごとの公開先一覧。
- site ごとの公開記事一覧。
- canonical 重複や canonical 未設定を検出する確認画面。
- site ごとの sitemap 対象件数確認。
- 公開先変更の audit summary。
- site 単位の権限を入れる場合は、どの管理者がどの site へ公開できるかを扱う権限管理。

監査ログでは、記事本文の更新と公開先の変更を混ぜない。

```text
article.updated
  = 記事本体の変更

article_site_publication.created
  = 記事が site の公開対象になった

article_site_publication.updated
  = site ごとの公開条件が変わった

article_site_publication.deleted
  = 記事が site の公開対象から外れた
```

SEO 面では、同じ記事本文を複数 site に出す場合、canonical を明示する必要がある。これは検索流入の水増しではなく、読者接点を増やすための再掲であることを構造で示すためである。canonical の決定は `article_site_publications.is_canonical` のような明示情報で扱い、front app 側の暗黙ルールにしない。

この設計では、記事中の段落、CTA、画像、見出しなどを細かい fragment として pool 化し、front 側で組み立て直すことはしない。この記事 model で扱う共有単位は記事本体であり、本文内の要素単位の再利用ではない。要素単位の pool 化は編集体験と責務境界を壊しやすく、この CMS の方針には合わない。

結論として、`article_site_publications` は「記事本体」と「site への公開」が業務上別概念として存在すると分かった場合にだけ導入する。現時点では初期実装へ入れず、`pages.site_id` と site 設定を先に整える。

## 備考2: articles.site_id と article_site_publications の比較

記事を site と紐づける方法は、大きく分けて `articles.site_id` を持たせる方法と、`article_site_publications` を持たせる方法がある。前者は「記事は 1 つの site に属する」という設計であり、後者は「記事本体と公開先 site は別」という設計である。

この2つは、単に正規化の度合いが違うだけではない。CMS 上の操作、front query、監査、SEO、編集責任の考え方が変わる。

| 観点 | `articles.site_id` | `article_site_publications` |
| --- | --- | --- |
| 意味 | 記事は 1 site に所属する | 記事本体は独立し、公開先 site を別管理する |
| schema | `articles` に `site_id` を追加するだけ | 中間の公開先テーブルが必要 |
| query | 単純。`articles.site_id = :siteId` で絞れる | 常に公開先テーブルとの join / exists が必要 |
| CMS 編集 | 記事編集画面で所属 site を選べばよい | 記事本文編集と公開先管理を分ける必要がある |
| 管理しやすさ | 高い。多くの運用者に説明しやすい | 低い。公開先という別概念を理解する必要がある |
| 実装コスト | 低い | 高い |
| バグの入りやすさ | 低い | 高い。公開状態、一覧表示、canonical の不整合が起きやすい |
| sitemap | site ごとの記事を単純に出せる | site ごとの publication を見て出す |
| canonical | 基本は自 site の記事 URL | 複数 site 公開時に canonical site を決める必要がある |
| 同一記事の複数 site 公開 | できない。複製が必要 | できる |
| site ごとの title / description | 基本は持たない。複製で対応 | publication 側に差し替え metadata を持てる |
| site ごとの公開期間 | 基本は持たない。記事本体の公開期間になる | publication 側で site ごとに持てる |
| site ごとの一覧表示制御 | 基本は記事本体の visibility になる | publication 側で制御できる |
| 監査 | 記事更新に集約しやすい | 記事本文更新と公開先変更を分ける必要がある |
| 権限 | site 所属記事の編集権限として扱いやすい | site への公開権限を別に考える必要がある |
| 記事複製 | 同じ内容を複数 site に出すなら複製する | 複製せずに同じ記事 ID を公開できる |
| 運用の見通し | 単純で追いやすい | 柔軟だが追う対象が増える |

`articles.site_id` で実現しやすい動作は次の通り。

- site ごとに記事を完全に分ける。
- site ごとの記事一覧、記事詳細、検索、関連記事、sitemap を単純な条件で実装する。
- 管理画面で「この記事はどの site の記事か」を分かりやすく表示する。
- site 単位の権限を、記事の所属 site に対する権限として扱う。
- 同じ内容の記事が必要な場合は、記事を複製して site ごとに管理する。

`article_site_publications` で実現しやすい動作は次の通り。

- 1 つの記事を複数 site に出す。
- site ごとに公開状態、公開期間、一覧表示、sitemap 対象を変える。
- site ごとに title / description / canonical など公開用 metadata を変える。
- 記事本文を更新したとき、複数 site の公開内容へ同時に反映する。
- 記事本体の編集責任と、どの site に出すかという公開責任を分ける。

`articles.site_id` のメリットは、単純さである。記事の所属、URL、一覧、検索、sitemap、権限、監査の全てが 1 site 所属を前提にできる。業務上「記事はサイトごとのもの」と言えるなら、この設計の方が強い。不要な join がなく、管理画面も説明しやすい。

`articles.site_id` のデメリットは、同じ記事本体を複数 site に出す場合に複製が必要になることである。複製した記事は、片方だけ修正される、canonical が曖昧になる、同じ内容の在庫が増える、という運用リスクを持つ。ただし、同じ記事を複数 site に出す頻度が低いなら、このリスクは schema を複雑にする理由にならない。

`article_site_publications` のメリットは、記事本体と公開先を分けられることである。同一記事を複数 site に出す、site ごとに公開条件を変える、site ごとに metadata を変える、といった要求には自然に対応できる。

`article_site_publications` のデメリットは、重さである。記事一覧や詳細表示のたびに publication 条件が必要になり、CMS では記事本文編集とは別に公開先管理が必要になる。監査ログも `article.updated` と `article_site_publication.*` を分ける必要がある。これは「汎用的にできるから」という理由で入れるには大きすぎるコストである。

判断基準は次の通り。

| 条件 | 推奨 |
| --- | --- |
| 記事は基本的に site ごとの資産である | `articles.site_id` |
| 同じ内容を別 site に出すことは稀で、複製で足りる | `articles.site_id` |
| site ごとの記事管理を単純にしたい | `articles.site_id` |
| 同一記事 ID を複数 site に正式公開したい | `article_site_publications` |
| site ごとに公開期間や metadata を変えたい | `article_site_publications` |
| 記事本文の更新を複数 site へ同時反映したい | `article_site_publications` |
| front の見た目変更やカテゴリ絞り込みだけで足りる | どちらも入れない |

現時点の判断としては、`articles.site_id` も `article_site_publications` も初期実装には入れない。理由は、現在の目的が「固定ページ、トップページ、site 設定、外部連携を公開面ごとに扱うこと」であり、記事の site 所属や複数 site 公開をまだ決めなくても成立するためである。

ただし、将来実装するなら、まず `articles.site_id` を第一候補として検討する。これは `article_site_publications` より機能が少ないからではなく、記事が 1 site 所属で済むなら構造が圧倒的に単純で、管理画面、query、監査、権限の全てが軽くなるためである。

`article_site_publications` は、`articles.site_id` では表現できない業務要求が明確になった場合だけ選ぶ。特に「同じ article ID を複数 site に公開し、しかも複製では運用できない」と言える場合に限って、導入する価値がある。

## 備考3: pera の公開範囲と実践実装の扱い

この文書は site model の計画であり、pera 自体の販売戦略や公開戦略を扱う資料ではない。ただし、`apps/front-business` や multi-site CMS の検討は、pera をどう見せるか、どこまで無償公開するかという判断にも関係するため、派生メモとして残す。

pera の無料公開範囲は、Framework の価値と入口の低さを示すことに絞る。

```text
minimal scaffold
  = 普通の PHP として小さく始められる入口

layered scaffold
  = 責務分離した実務向け構成の基本形

docs / benchmark
  = pera の設計思想、性能、HTTP / DI / Routing / View / Security の説明
```

`apps/minimal` は、Controller に処理をまとめて書くような最小構成でも、pera の HTTP 境界、DI、ITemplateRenderer、FrankenPHP worker 実行基盤の恩恵を受けられることを示すための入口である。これは「低品質な書き方を推奨する」ためではなく、「雑に始めても土台の品質は落ちにくい」ことを示すためにある。

そのため、`minimal` だけを前面に出すと、pera が単なる PDO 直書きの薄い framework だと誤解される可能性がある。見せ方としては、次の順序を明確にする。

```text
minimal
  = まず動く。普通の PHP として書ける。

layered
  = 境界を分けて実務向けに育てられる。

private practical implementation
  = 監査、認可、履歴、CMS 運用、公開面分離などの実践知。
```

`cms/webmedia` の実装は、無料サンプルとして公開しない。これは Framework の使い方を示すだけの教材ではなく、監査、認可、履歴、管理画面、公開面分離、Presentation / Application / Domain の切り方、運用上の判断を含む実践実装であり、業務知見そのものに近い。

無償公開してよいのは、Framework の核と、利用者が pera を始めるための入口である。実務向け CMS 実装や監査 middleware を含む具体構成は、次のいずれかとして扱う。

- 有償テンプレート。
- 契約顧客向け scaffold。
- コンサルティング時の非公開参考実装。
- 自社サービス基盤。
- 公開する場合でも、コードではなく概要説明に留める実践投入例。

公開説明としては、次の程度に留める。

```text
pera は実業務向け CMS 実装でも利用できる。
認証、認可、監査ログ、履歴管理、管理画面、公開面分離などを含む構成で運用可能である。
```

ここで重要なのは、pera の価値を見せるために実践実装の中身まで無料で差し出さないことである。`minimal` と `layered` は入口として公開し、`cms/webmedia` のような業務知見を含む実装は別価値として守る。

この線引きにより、pera は次の2つを両立できる。

- 小さく始められる framework としての採用しやすさ。
- 実務品質の設計を安売りしないこと。
