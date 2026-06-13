# CMS App Directory Roles

`apps/cms` は管理画面のアプリケーションです。認証、記事管理、固定ページ管理、カテゴリ/タグ管理、メディア管理、問い合わせ確認、設定管理を担当します。

## Directory Roles

- `config/`: route、container、middleware など、CMS app の合成設定を置く。
- `src/Application/`: UseCase を置く。`<対象>/<ユースケース>` ごとに `Input`、`Interactor`、`Output`、必要な `Query` interface と `Value` DTO を持つ。
- `src/Domain/`: CMS app 固有の業務概念と port を置く。永続化のための Repository interface はここに置く。
- `src/Presentation/`: HTTP/HTML としてどう見せるかを扱う。Controller、管理画面用 ViewModel など、表現上の関心事を置く。詳細は `src/Presentation/README.md` を参照する。
- `src/Presentation/Http/Controller/`: Request を受け、Application Interactor を呼び、Response または template params を組み立てる。
- `src/Infrastructure/`: DB、外部I/O、技術的 adapter を置く。Application Query interface や Domain Repository interface の実装はここに置く。
- `src/Infrastructure/Http/Middleware/`: CMS 固有の HTTP middleware を置く。将来 middleware が増え、責務の違いが明確になった場合だけ `Presentation/Http/Middleware` との分離を検討する。
- `templates/`: Latte template を置く。Controller が渡した view data を HTML として表現する。

## Dependency Rules

- `Presentation` は `Application` を呼んでよい。
- `Presentation` は `Infrastructure/Persistence` の具象実装へ直接依存しない。
- `Application` は `Infrastructure` と `Presentation` に依存しない。
- `Domain` は `Application`、`Presentation`、`Infrastructure` に依存しない。
- `Infrastructure` は Application Query interface や Domain Repository interface を実装してよい。

## Notes

CMS の Controller は Front と同じく表示層の入口なので `Presentation/Http/Controller` に置きます。認証必須判定などの middleware は HTTP pipeline に差し込むアプリ固有の基盤部品として `Infrastructure/Http/Middleware` に置きます。
