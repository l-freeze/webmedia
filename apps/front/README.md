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
