# Front App Directory Roles

`apps/front` は読者向け公開画面のアプリケーションです。CMS とは別アプリとして扱い、公開済みコンテンツの表示、検索、分類ページ、問い合わせ導線を担当します。

## Directory Roles

- `config/`: route、container、middleware など、Front app の合成設定を置く。
- `src/Application/`: UseCase を置く。`<対象>/<ユースケース>` ごとに `Input`、`Interactor`、`Output`、必要な `Query` interface と `Value` DTO を持つ。
- `src/Domain/`: Front app 固有の業務概念と port を置く。永続化のための Repository interface はここに置く。
- `src/Presentation/`: HTTP/HTML としてどう見せるかを扱う。Controller、表示用URL、SEO meta など、テンプレートへ渡す表現上の関心事を置く。
- `src/Presentation/Http/Controller/`: Request を受け、Application Interactor を呼び、Response または template params を組み立てる。
- `src/Presentation/Seo/`: canonical URL など、HTML head に出す表示メタ情報の生成を置く。
- `src/Infrastructure/`: DB、外部I/O、技術的 adapter を置く。Application Query interface や Domain Repository interface の実装はここに置く。
- `templates/`: Latte template を置く。Controller が渡した view data を HTML として表現する。

## Dependency Rules

- `Presentation` は `Application` を呼んでよい。
- `Presentation` は `Infrastructure/Persistence` の具象実装へ直接依存しない。
- `Application` は `Infrastructure` と `Presentation` に依存しない。
- `Domain` は `Application`、`Presentation`、`Infrastructure` に依存しない。
- `Infrastructure` は Application Query interface や Domain Repository interface を実装してよい。

## Notes

`Presentation` は「アプリケーション都合ではあるが、UseCase そのものではない表示責務」を置く層です。canonical URL、OGP、JSON-LD、ViewModel などが増える場合は、UseCase や Infrastructure に混ぜず `Presentation` 配下へ置きます。

## Presentation Layer Policy

`Presentation` は Front app の外向きの表現を担当します。ここでいう表現とは、HTTP request/response、HTML template へ渡す値、HTML head の meta 情報、検索エンジンや SNS に向けた公開URLの見せ方などです。

この層を置く目的は、`Application` と `Infrastructure` の意味を濁らせないことです。

- `Application`: ユーザーやシステムが達成したいユースケースを表す。記事を表示する、カテゴリの記事を取得する、問い合わせを作成する、など。
- `Infrastructure`: DB、外部API、メール送信、ファイルI/Oなど、技術的な入出力の詳細を表す。
- `Presentation`: 取得した結果を HTTP/HTML としてどう見せるかを表す。

canonical URL は「記事詳細を表示する」という UseCase そのものではありません。また、DB や外部I/O の実装詳細でもありません。HTML head に出す公開URLという表示上の判断なので `Presentation/Seo` に置きます。

同じ理由で、今後 sitemap、OGP、Twitter Card、JSON-LD、robots meta、noindex 制御、パンくず、画面専用 ViewModel などを追加する場合も、まず `Presentation` 配下に置くことを検討します。

## Why This Improves Maintainability

変更前のように Controller や SEO meta を `Infrastructure` 配下へ置くと、`Infrastructure` が「外部技術の詳細」ではなく「HTTP表示まわり全般の置き場」になりやすくなります。短期的には動きますが、SEOや画面表現が増えたときに次のような迷いが出ます。

- OGP を `Infrastructure/Seo` に置くのか、`Application/Seo` に UseCase として置くのか判断しづらい。
- JSON-LD や sitemap が増えたとき、DB adapter と HTML 表現が同じ `Infrastructure` の意味に混ざる。
- API Controller と HTML Controller が増えたとき、HTML head 用の処理をどこまで共有してよいか分かりづらい。
- ViewModel や template 専用 DTO が必要になったとき、UseCase の Output と表示専用の整形値が混ざりやすい。

現在の構成では、これらは Front の外向き表現として説明できます。

- HTML Controller は `Presentation/Http/Controller` に置く。
- canonical、OGP、JSON-LD、robots meta などは `Presentation/Seo` に置く。
- sitemap の HTTP endpoint は `Presentation/Http/Controller` に置き、URL列挙やXML出力用の表現組み立ては `Presentation/Seo` または sitemap 専用の `Presentation/Sitemap` に置く。
- DB から公開記事や固定ページを取得する処理は `Application` の Query interface と `Infrastructure/Persistence` の実装に分ける。

この変更は「以前の構成では実装できなかったものを可能にする」変更ではありません。以前の構成でも PHP としては実装できます。品質向上の中心は、将来の変更時に責務を説明しやすくし、ファイルの置き場所を迷いにくくすることです。

定量的には、Front の表示責務に関する保守性はおおよそ `+10〜15点 / 100点満点` 程度改善したと見なします。特に canonical だけでなく、sitemap、OGP、JSON-LD、noindex 制御、検索結果ページ、タグ/カテゴリページのSEO制御が増えるほど効果が大きくなります。

## Sitemap Placement

sitemap は検索エンジン向けの公開表現なので、Front の `Presentation` に属します。ただし、sitemap に載せる記事、カテゴリ、タグ、固定ページを選ぶ条件は Application の責務です。

想定する分担は次の通りです。

- `Application/Sitemap/...`: sitemap に必要な公開URL候補を取得する UseCase、Query interface、DTO を置く。
- `Infrastructure/Persistence/...`: DB から公開記事、カテゴリ、タグ、固定ページを取得する Query 実装を置く。
- `Presentation/Http/Controller/SitemapController`: `/sitemap.xml` の request を受け、UseCase を呼び、XML response を返す。
- `Presentation/Sitemap/...` または `Presentation/Seo/...`: URL entry の整形、XML用の表現組み立て、lastmod の表示形式などを置く。

`Presentation` が DB を直接読みに行く形にはしません。Controller は Application を呼び、Application は interface 越しに必要なデータを受け取り、Presentation が HTTP/XML として出力します。
