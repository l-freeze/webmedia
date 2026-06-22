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

CMS 固有 middleware は `apps/cms/src/Presentation/Http/Middleware` に置きます。現在の CMS middleware は、ログイン状態、route-level permission、CMS 固有の CSRF security event、CMS request audit event など、すべて HTTP request/response の入口制御と表現に関わるためです。

ただし将来 middleware が増え、すべてを Infrastructure に置く方がかえって分かりにくくなった場合は、次の基準で分離を検討します。

- `Presentation/Http/Middleware/`: 画面、ルート、ログイン状態、表示モード、SEO、redirect、CMS 固有の認証/認可入口、CMS 固有 event 発火など、CMS の HTTP 表現に直接関わるもの。
- `Infrastructure/Http/Middleware/`: proxy header、upload limit、外部基盤都合の request/response 制約など、技術的な入出力や実行基盤に近いものが必要になった場合だけ作る。
- `src/Framework/Http/Middleware/`: CMS/Front に依存しない middleware interface、pipeline、共通実行機構。

現在 `apps/cms/src/Infrastructure/Http` は使いません。Infrastructure に置くべき HTTP 入出力実装が出た時点で追加します。
