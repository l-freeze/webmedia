# Front Presentation Directory

`Presentation` は Front app の外向きの表現を担当する層です。HTTP request/response、HTML template へ渡す値、HTML head の meta 情報、検索エンジンや SNS に向けた公開URLの見せ方、sitemap XML などを扱います。

ここに置くものは「アプリケーション都合ではあるが、UseCase そのものではない表示責務」です。

## Directory Roles

- `Http/Controller/`: Request を受け、Application Interactor を呼び、Response または template params を組み立てる。
- `Seo/`: canonical URL、OGP、Twitter Card、JSON-LD、robots meta、noindex 制御など、HTML head や検索/SNS向けの表示メタ情報を置く。
- `Sitemap/`: sitemap XML の entry 整形、URL set の組み立て、lastmod の表現変換などを置く。必要になった時点で作成する。
- `ViewModel/`: template 専用の表示モデルを置く。UseCase Output をそのまま HTML に渡すと表示都合が混ざる場合に作成する。

## Responsibility Boundary

この層を置く目的は、`Application` と `Infrastructure` の意味を濁らせないことです。

- `Application`: ユーザーやシステムが達成したいユースケースを表す。記事を表示する、カテゴリの記事を取得する、問い合わせを作成する、sitemap 用の公開URL候補を取得する、など。
- `Infrastructure`: DB、外部API、メール送信、ファイルI/Oなど、技術的な入出力の詳細を表す。
- `Presentation`: 取得した結果を HTTP/HTML/XML としてどう見せるかを表す。

`Presentation` が DB を直接読みに行く形にはしません。Controller は Application を呼び、Application は Query interface や Repository interface 越しに必要なデータを受け取り、Presentation が HTTP/HTML/XML として出力します。

## Why This Layer Exists

変更前のように Controller や SEO meta を `Infrastructure` 配下へ置くと、`Infrastructure` が「外部技術の詳細」ではなく「HTTP表示まわり全般の置き場」になりやすくなります。短期的には動きますが、SEOや画面表現が増えたときに次のような迷いが出ます。

- OGP を `Infrastructure/Seo` に置くのか、`Application/Seo` に UseCase として置くのか判断しづらい。
- JSON-LD や sitemap が増えたとき、DB adapter と HTML/XML 表現が同じ `Infrastructure` の意味に混ざる。
- API Controller と HTML Controller が増えたとき、HTML head 用の処理をどこまで共有してよいか分かりづらい。
- ViewModel や template 専用 DTO が必要になったとき、UseCase Output と表示専用の整形値が混ざりやすい。

現在の構成では、これらは Front の外向き表現として説明できます。

## Placement Examples

### canonical URL

canonical URL は「記事詳細を表示する」という UseCase そのものではありません。また、DB や外部I/O の実装詳細でもありません。HTML head に出す公開URLという表示上の判断なので `Seo` に置きます。

```text
apps/front/src/Presentation/Seo/CanonicalUrl.php
apps/front/src/Presentation/Http/Controller/Article/ArticleShowController.php
apps/front/templates/layout/default.latte
```

Controller は必要なページでのみ canonical URL を生成し、template params として layout に渡します。canonical を出さないページでは渡しません。

### OGP

OGP は SNS 向けの HTML meta 表現なので `Seo` に置きます。記事詳細、固定ページ、カテゴリページなどで必要な値が違う場合は、ページ種別ごとに生成クラスを分けます。

```text
apps/front/src/Presentation/Seo/Ogp/OgpMeta.php
apps/front/src/Presentation/Seo/Ogp/ArticleOgpMetaFactory.php
apps/front/src/Presentation/Seo/Ogp/PageOgpMetaFactory.php
apps/front/src/Presentation/Http/Controller/Article/ArticleShowController.php
apps/front/templates/layout/default.latte
```

`OgpMeta` は `title`、`description`、`url`、`imageUrl`、`type` などの表示用 DTO として扱います。記事本文の取得や公開判定は Application 側で終わらせ、OGP クラスは表示用の組み立てに集中します。

### Twitter Card

Twitter Card も HTML meta 表現なので `Seo` に置きます。OGP と重複する値が多い場合でも、出力仕様が違うなら専用 DTO を作って構いません。

```text
apps/front/src/Presentation/Seo/TwitterCard/TwitterCardMeta.php
apps/front/src/Presentation/Seo/TwitterCard/ArticleTwitterCardFactory.php
apps/front/src/Presentation/Seo/TwitterCard/PageTwitterCardFactory.php
apps/front/templates/layout/default.latte
```

OGP と Twitter Card の共通化は、重複が実際に増えてから検討します。最初から汎用的な `SocialMetaService` に寄せすぎると、各仕様の違いが見えにくくなります。

### JSON-LD

JSON-LD は検索エンジン向けの構造化データ表現なので `Seo` に置きます。HTMLに埋め込む JSON の形を作るだけに留め、記事の公開条件やカテゴリ取得は Application に任せます。

```text
apps/front/src/Presentation/Seo/JsonLd/ArticleJsonLd.php
apps/front/src/Presentation/Seo/JsonLd/BreadcrumbJsonLd.php
apps/front/src/Presentation/Seo/JsonLd/WebSiteJsonLd.php
apps/front/templates/layout/default.latte
```

### robots meta / noindex

検索結果ページ、プレビュー、重複しやすい一覧ページなどで `noindex` を制御する場合も `Seo` に置きます。

```text
apps/front/src/Presentation/Seo/RobotsMeta.php
apps/front/src/Presentation/Http/Controller/Search/SearchController.php
apps/front/templates/layout/default.latte
```

Controller がページごとの判断を行い、`RobotsMeta` を template params に渡します。

### sitemap

sitemap は検索エンジン向けの公開表現なので `Presentation` に属します。ただし、sitemap に載せる記事、カテゴリ、タグ、固定ページを選ぶ条件は Application の責務です。

想定する分担は次の通りです。

```text
apps/front/src/Application/Sitemap/Build/SitemapBuildInput.php
apps/front/src/Application/Sitemap/Build/SitemapBuildInteractor.php
apps/front/src/Application/Sitemap/Build/SitemapBuildOutput.php
apps/front/src/Application/Sitemap/Query/SitemapEntryQuery.php
apps/front/src/Application/Sitemap/Value/SitemapEntry.php

apps/front/src/Infrastructure/Persistence/Sitemap/PdoSitemapEntryQuery.php

apps/front/src/Presentation/Http/Controller/Sitemap/SitemapController.php
apps/front/src/Presentation/Sitemap/SitemapXml.php
apps/front/src/Presentation/Sitemap/SitemapUrl.php
```

`Application` は sitemap に載せるURL候補を返します。`Presentation/Sitemap` はその候補を XML としてどう出すかを担当します。`lastmod` の日時形式、URLの絶対化、XML escaping などは Presentation 側の責務です。

### ViewModel

UseCase Output を template にそのまま渡すと、表示都合の整形や複数DTOの合成が Controller に増えすぎる場合は `ViewModel` を作ります。

```text
apps/front/src/Presentation/ViewModel/Article/ArticleDetailViewModel.php
apps/front/src/Presentation/ViewModel/Article/ArticleCardViewModel.php
apps/front/src/Presentation/ViewModel/Layout/HeaderNavigationViewModel.php
```

ViewModel は HTML の見せ方に寄せた値を持ちます。UseCase の戻り値を置き換えるものではありません。

## Dependency Rules

- `Presentation` は `Application` を呼んでよい。
- `Presentation` は `Infrastructure/Persistence` の具象実装へ直接依存しない。
- `Presentation` は Domain Entity を template 都合で変更しない。
- `Application` は `Presentation` に依存しない。
- `Infrastructure` は `Presentation` に依存しない。

## Maintainability Impact

この変更は「以前の構成では実装できなかったものを可能にする」変更ではありません。以前の構成でも PHP としては実装できます。品質向上の中心は、将来の変更時に責務を説明しやすくし、ファイルの置き場所を迷いにくくすることです。

定量的には、Front の表示責務に関する保守性はおおよそ `+10〜15点 / 100点満点` 程度改善したと見なします。特に canonical だけでなく、sitemap、OGP、Twitter Card、JSON-LD、noindex 制御、検索結果ページ、タグ/カテゴリページのSEO制御が増えるほど効果が大きくなります。
