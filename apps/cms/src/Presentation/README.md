# CMS Presentation Directory

`Presentation` は CMS app の外向きの表現を担当する層です。管理画面の HTTP request/response、Latte template へ渡す値、redirect、flash message、validation error の表示などを扱います。

ここに置くものは「管理画面の表示都合ではあるが、UseCase そのものではない責務」です。

## Directory Roles

- `Http/Controller/`: Request を受け、Application Interactor を呼び、Response または template params を組み立てる。
- `ViewModel/`: template 専用の表示モデルを置く。UseCase Output をそのまま HTML に渡すと表示都合が混ざる場合に作成する。

## Responsibility Boundary

- `Application`: 管理画面で達成したいユースケースを表す。記事を保存する、履歴を表示する、カテゴリを更新する、メディアをアップロードする、など。
- `Infrastructure`: DB、外部I/O、セッション実装、メール送信、ファイルI/Oなど、技術的な入出力の詳細を表す。
- `Presentation`: 取得した結果や処理結果を HTTP/HTML としてどう見せるかを表す。

`Presentation` が DB を直接読みに行く形にはしません。Controller は Application を呼び、Application は Query interface や Repository interface 越しに必要なデータを受け取り、Presentation が HTTP response や template params に変換します。

## Controller Placement

CMS Controller は Front Controller と同じく表示層の入口なので、`Infrastructure` ではなく `Presentation/Http/Controller` に置きます。

```text
apps/cms/src/Presentation/Http/Controller/Article/List/Controller.php
apps/cms/src/Presentation/Http/Controller/Article/Create/Controller.php
apps/cms/src/Presentation/Http/Controller/Article/Store/Controller.php
apps/cms/src/Presentation/Http/Controller/Page/Edit/Controller.php
apps/cms/src/Presentation/Http/Controller/Media/Store/Controller.php
apps/cms/src/Presentation/Http/Controller/Auth/Login/Controller.php
```

Controller の責務は次に限定します。

- framework HTTP request を受け取る。
- primitive な入力値を取り出す。
- Application `Input` を作る。
- Application `Interactor` を呼ぶ。
- `Output` を HTTP response、redirect、または template params へ変換する。
- flash message や CSRF token など、画面表示に必要な値を template に渡す。

Controller に業務ルール、SQL、永続化処理を書いてはなりません。

## ViewModel Placement

UseCase Output を template にそのまま渡すと、表示都合の整形や複数DTOの合成が Controller に増えすぎる場合は `ViewModel` を作ります。

```text
apps/cms/src/Presentation/ViewModel/Article/ArticleEditViewModel.php
apps/cms/src/Presentation/ViewModel/Article/ArticleHistoryViewModel.php
apps/cms/src/Presentation/ViewModel/Media/MediaListViewModel.php
apps/cms/src/Presentation/ViewModel/Layout/AdminNavigationViewModel.php
```

ViewModel は HTML の見せ方に寄せた値を持ちます。UseCase の戻り値を置き換えるものではありません。

## Dependency Rules

- `Presentation` は `Application` を呼んでよい。
- `Presentation` は `Infrastructure/Persistence` の具象実装へ直接依存しない。
- `Presentation` は Domain Entity を template 都合で変更しない。
- `Application` は `Presentation` に依存しない。
- `Infrastructure` は `Presentation` に依存しない。

## Middleware

CMS 固有 middleware は現状 `apps/cms/src/Infrastructure/Http/Middleware` に置きます。Controller と同じ HTTP 周辺ではありますが、今回の移動対象は「画面入力を受けて UseCase を呼び Response を返す Controller」です。

middleware の責務を Presentation に寄せるかは、認証、監査、request 制約などの扱いを改めて整理するタイミングで判断します。
