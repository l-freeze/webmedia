# 開発知識メモ

## 認証・認可まわりの用語

認証、認可、middleware、guard、policy は混同しやすい。特に `auth` という略語は `authentication` と `authorization` の両方に見えるため、設計時は明確に分ける。

## Authentication / 認証

認証は「誰なのか」を確認すること。

例:

```text
ログイン済みか
セッションに user_id があるか
API token が正しいか
その user_id のユーザーが存在するか
```

CMS では次のような責務になる。

```text
Session を見る
cms.user_id を確認する
必要なら UserRepository でユーザーを取得する
CurrentUser を作る
未ログインならログイン画面へ redirect する
```

## Authorization / 認可

認可は「その人がその操作をしてよいか」を確認すること。

例:

```text
記事を削除してよいか
設定を更新してよいか
公開済み記事を編集してよいか
承認操作をしてよいか
```

認可は、認証済みのユーザー情報を前提にする。ただし、ユーザーがどうやって認証されたかには関与しない。

```text
Guard:
  この人は誰か？

Authorization:
  この人はこれをしてよいか？
```

## Middleware

Middleware は HTTP request が Controller に届く前後に挟まる処理。

向いている処理:

```text
ログイン必須チェック
ルート単位の権限チェック
CSRF チェック
request id の付与
アクセスログ
例外ハンドリング
```

Middleware は「処理の通過点」であり、特定の認証・認可ロジックそのものではない。

例:

```text
Request
  -> cms.auth middleware
  -> cms.can middleware
  -> Controller
```

## Guard

Guard は認証境界を担当する部品。

主な責務:

```text
現在のリクエストがログイン済みか判定する
現在ユーザーを取得する
未認証なら null または例外を返す
必要なら CurrentUser を作る
```

Guard は「何をしてよいか」までは基本的に判断しない。

比喩:

```text
Guard:
  入場確認係
  身元を確認する
  プロフィールを把握する

Authorization:
  操作許可の判定係
  そのプロフィールでその操作をしてよいか判断する
```

CMS なら次のような構成が自然。

```text
CmsAuthMiddleware
  -> CmsGuard
      -> Session
      -> UserRepository
      -> CurrentUser
```

小さい実装では、最初は `CmsAuthMiddleware` が直接 `Session` を見てもよい。ただし認証判定が複雑になったら `CmsGuard` に分ける。

## CurrentUser

CurrentUser は、認証済みユーザーをアプリケーション内で扱うためのオブジェクト。

持たせる情報の例:

```text
user id
role
display name
email
isAuthenticated
```

UseCase に「誰が実行しているか」を渡す場合、`Session` をそのまま渡すのではなく `CurrentUser` を渡す方がよい。

```php
new SaveInput(
    actor: $currentUser,
    title: $title,
    body: $body,
);
```

Guard が `CurrentUser` を作り、Controller / Request DTO / Interactor が必要に応じて利用する。

## Permission

Permission は「許可される操作」の名前。

画面名ではなく操作名にする。

良い例:

```text
article.list
article.create
article.update
article.delete
article.publish
setting.update
media.delete
```

悪い例:

```text
article_screen
admin_page
edit_button
```

画面名やボタン名で権限を作ると、あとで操作単位の制御が難しくなる。

## Role

Role は Permission の集合。

例:

```text
admin
editor
writer
viewer
```

Role はユーザーの肩書きであり、Permission は実際に許可される操作。

```text
Role:
  editor

Permissions:
  article.list
  article.update
  article.publish
```

小規模なうちは role-to-permission mapping をコード上に持ってよい。管理画面でロールを編集したい段階になってから DB 化すればよい。

## AuthorizationService

AuthorizationService は、CurrentUser と Permission を受け取り、許可するか判定する部品。

例:

```php
$authorization->can($currentUser, 'article.update');
$authorization->authorize($currentUser, 'article.update');
```

`can()` は bool を返す。`authorize()` は許可されなければ例外を投げる、という使い分けができる。

Middleware からも UseCase からも利用できるようにするとよい。

```text
CmsPermissionMiddleware
  -> AuthorizationService

ArticleSaveInteractor
  -> AuthorizationService
```

## Policy

Policy はリソース固有の認可ルールを扱う。

Permission だけでは表現できない条件がある場合に使う。

例:

```text
この記事は本人が作ったものか
この記事は公開済みか
このユーザーはこのカテゴリの記事を編集できるか
この操作は管理者だけか
```

例:

```php
$articlePolicy->canUpdate($currentUser, $article);
$articlePolicy->canPublish($currentUser, $article);
```

単純な権限チェックは `AuthorizationService`、対象オブジェクトの状態を見る判定は `Policy` に寄せる。

Permission と Policy の違い:

```text
Permission:
  article.update という操作権限を持っているか

Policy:
  このユーザーが、この article を update してよいか
```

中小規模なら、最初は Permission だけで十分な場合が多い。

次のような条件が出てきたら Policy を追加する。

```text
自分の記事だけ編集できる
公開済み記事は編集者以上だけ編集できる
特定カテゴリの記事は特定ロールだけ編集できる
削除は作成者ではなく管理者だけできる
```

## Gate

Gate は Laravel などで使われる認可の入口の名前。

一般的には `AuthorizationService` に近い。

```text
Gate::allows('article.update', $article)
```

このプロジェクトでは Laravel 互換を目指していないため、`Gate` という名前を無理に使う必要はない。`AuthorizationService` の方が意図が明確。

## Middleware と UseCase の二重チェック

認可は middleware だけでは不十分なことが多い。

Middleware が得意なこと:

```text
このルートに入ってよいか
この大まかな操作権限があるか
```

UseCase / Policy が得意なこと:

```text
このユーザーがこの特定の記事を更新してよいか
この状態の記事を公開してよいか
この削除操作は承認待ちにするべきか
```

基本方針:

```text
Route middleware:
  入口で大まかに止める

UseCase / Policy:
  業務操作の直前で最終判定する
```

UI でボタンを隠すだけでは認可にならない。直接 POST されても拒否できるようにする。

## Request DTO と Guard の違い

Request DTO は「何を入力されたか」を整理する。

Guard は「誰が実行しているか」を整理する。

```text
Request DTO / Controller:
  title
  body
  status
  category_id

Guard / CurrentUser:
  actor user id
  actor role
  actor display name
```

UseCase Input には、必要に応じて両方を含めてよい。

```php
new SaveInput(
    actor: $currentUser,
    title: $requestInput->title,
    body: $requestInput->body,
);
```

Guard が UseCase Input を直接作るわけではない。

## Approval Workflow / 承認ワークフロー

承認ワークフローは認可とは別の概念。

認可:

```text
この人はこの操作をしてよいか？
```

承認ワークフロー:

```text
この操作は即時反映してよいか？
承認待ちとして保存するべきか？
誰が承認できるか？
```

例:

```text
writer:
  下書き保存はできる
  公開はできない
  公開申請はできる

editor:
  公開申請を承認できる
  差し戻しできる

admin:
  直接公開できる
```

権限チェックを入れた後に、必要になった段階で承認ワークフローを設計する方が安全。

## Audit Log / 監査ログ

監査ログは「誰が、いつ、何をしたか」を残す業務証跡。

通常ログとは目的が違う。

通常ログ:

```text
エラー
例外
処理時間
デバッグ情報
```

監査ログ:

```text
article.updated
article.deleted
setting.updated
authorization.denied
login.succeeded
login.failed
```

監査ログには、実行ユーザー、対象リソース、結果、IP、user agent、request id、before/after などを含める。

このプロジェクトでは `AuditLoggerInterface` を使う。

## PSR-11 / Container

PSR-11 は Container の標準 interface。

主に次の2つを定義している。

```php
$container->get(string $id): mixed;
$container->has(string $id): bool;
```

このプロジェクトの `src/Framework/Container/Container.php` は `Psr\Container\ContainerInterface` を実装しているため、PSR-11 に従っている。

例外も PSR-11 に合わせる。

```text
NotFoundExceptionInterface
  サービスが見つからない

ContainerExceptionInterface
  サービス構築に失敗した
```

PSR-11 は「Container をどう設定するか」までは決めない。

つまり、次のようなメソッドは独自で持ってよい。

```php
$container->bind(...);
$container->singleton(...);
$container->instance(...);
```

PSR-11 が決めるのは、外部から Container を使う時の最小共通 interface。

## PSR-7

PSR-7 は HTTP message の標準。

主な interface:

```text
RequestInterface
ServerRequestInterface
ResponseInterface
StreamInterface
UriInterface
UploadedFileInterface
```

PSR-7 は immutable な HTTP request / response を扱う設計で、middleware ecosystem と相性がよい。

ただし、自前軽量フレームワークでは必ず採用しなければならないものではない。

採用するメリット:

```text
既存 middleware と連携しやすい
HTTP message の表現が標準化される
外部ライブラリとつなぎやすい
テスト用の実装を流用しやすい
```

採用しない理由:

```text
小規模アプリには interface が重い
StreamInterface などが過剰に感じる場合がある
自前 Request / Response の方が理解しやすい
学習・制御目的で HTTP 層を単純に保ちたい
PSR-7 の immutable 前提が既存設計と合わない場合がある
```

上級者でも、目的によってはあえて PSR-7 を採用しないことはある。

重要なのは、PSR-7 を知らずに外すのではなく、トレードオフを理解して外すこと。

このプロジェクトでは、現時点では自前の `Request` / `Response` で十分。PSR-7 に寄せる必要が出るのは、外部 middleware や HTTP ライブラリとの連携を強く求める段階。

## PSR-15

PSR-15 は HTTP middleware と request handler の標準。

PSR-15 の middleware は概ね次の形。

```php
interface MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
    ): ResponseInterface;
}
```

PSR-15 は PSR-7 の `ServerRequestInterface` / `ResponseInterface` とセットで使う前提が強い。

採用するメリット:

```text
標準 middleware を利用しやすい
middleware pipeline の形が明確
framework 交換や外部連携に強い
HTTP 処理の責務分離がしやすい
```

採用しない理由:

```text
PSR-7 も同時に必要になりやすい
小規模な独自 Router には重い場合がある
callable $next 形式の方が単純に実装できる
認証・CSRF・ログ程度なら独自 middleware で足りる
```

Laravel 風の軽い middleware は次のような形。

```php
public function handle(Request $request, callable $next): Response
```

このプロジェクトでは、まずこの軽い形で十分。

PSR-15 に合わせるべきタイミング:

```text
PSR-7 を採用する
外部 middleware を使いたい
HTTP stack をライブラリとして切り出したい
複数アプリで共通 middleware ecosystem を作りたい
```

PSR-15 を採用しないこと自体は問題ではない。小規模で制御された framework なら、独自 middleware の方が読みやすく保守しやすいことがある。

## 境界を守るためのテスト

境界を守るためのテストは、機能テストではなくアーキテクチャテスト。

目的:

```text
依存方向を破っていないか
禁止レイヤーへ依存していないか
技術詳細が内側へ漏れていないか
front / cms が相互依存していないか
```

例:

```text
Application が Infrastructure を use していない
Domain が Application / Infrastructure を use していない
front が cms を use していない
cms が front を use していない
PDO が Infrastructure 以外に出ていない
Controller が Persistence を直接 use していない
```

実装方法は単純でよい。

PHPUnit で対象ファイルを読み、禁止 namespace や禁止文字列を検出するだけでも効果がある。

例:

```php
self::assertStringNotContainsString(
    'App\\Cms\\Infrastructure',
    file_get_contents($applicationFile),
);
```

または `nikic/php-parser` を使って `use` 文だけを見ると、より正確に判定できる。

最初は文字列ベースで十分。ルールが増えて誤検出が気になったら AST ベースへ移行する。

境界テストは、設計ルールを人間の記憶に頼らないための安全網である。

## このプロジェクトでの推奨命名

```text
CmsAuthMiddleware
  未ログインを止める middleware

CmsPermissionMiddleware
  権限不足を止める middleware

CmsGuard
  CMSログイン状態と CurrentUser を扱う

CurrentUser
  認証済みユーザーのプロフィール

AuthorizationService
  権限判定の入口

Permission
  操作権限名

Policy
  リソース固有の認可ルール
```

## よくある混同

### auth は曖昧

`auth` は authentication と authorization の両方に見える。

設計ドキュメントでは次のように明記する。

```text
authentication = 認証
authorization  = 認可
```

### Guard は認可ではない

Guard は基本的に認証側の部品。

```text
Guard:
  お前は誰だ？

Authorization:
  お前はこれをしていいか？
```

### Middleware は認証そのものではない

Middleware は処理を挟む仕組み。

認証 middleware の中で guard を使うことはあるが、middleware と guard は同じではない。

### Policy は権限表そのものではない

Policy は個別ルールの判定担当。

Role / Permission は権限データ。Policy はそのデータやリソース状態を使って判断する。

### UI の表示制御は認可ではない

ボタンを隠しても、直接 POST されれば操作できてしまう。

表示制御は補助。強制チェックは middleware / UseCase / Policy で行う。

## 実装時の判断基準

最初から全部を作らない。

段階:

1. `CmsAuthMiddleware` でログイン必須チェックを共通化する。
2. 必要なら `CmsGuard` と `CurrentUser` に分ける。
3. Permission 名を定義する。
4. `AuthorizationService` を追加する。
5. `CmsPermissionMiddleware` を追加する。
6. 重要な UseCase だけ UseCase-level check を追加する。
7. 必要になってから Policy を追加する。
8. さらに必要になってから承認ワークフローを追加する。

この順序なら、過剰設計になりにくく、既存の Controller 重複も段階的に減らせる。
