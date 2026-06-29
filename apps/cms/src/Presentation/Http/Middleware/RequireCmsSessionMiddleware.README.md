# RequireCmsSessionMiddleware

`RequireCmsSessionMiddleware` は CMS 管理画面にログイン済みセッションを要求します。

## 対象

`/admin/login` はログインフォーム表示とログイン処理のため public path として通します。

それ以外の CMS リクエストで `cms.user_id` が Session に存在しない、または Session 上の user id から現在ユーザーを解決できない場合は `/admin/login` へ redirect します。

## 依存

ログイン済みユーザーの解決は `App\Cms\Infrastructure\Security\CmsGuard` に委譲します。

middleware は HTTP pipeline の入口制御だけを担当し、Session の値の正規化、ユーザー取得、現在ユーザー表現の組み立ては Guard 側に寄せます。

Guard は認証境界であり、Permission や Policy の判定は行いません。権限差の強制は、後続の permission middleware、`AuthorizationService`、UseCase / Policy 側で扱います。

`CurrentCmsUser` の解決には `ICurrentCmsUserQuery` が必要です。CMS container では次の binding を維持します。

```php
$container->bind(
    abstract: Auth\CurrentUser\Query\ICurrentCmsUserQuery::class,
    concrete: PdoCurrentCmsUserQuery::class,
);
```

## 登録

`RequireCmsSessionMiddleware` は CMS 固有 middleware の先頭に置きます。

```php
return [
    ...$sharedMiddleware,
    RequireCmsSessionMiddleware::class,
    RequireCmsPermissionMiddleware::class,
    AuditMiddleware::class,
];
```

未ログイン request を先に止めることで、後続 middleware はログイン済みユーザーを前提にできます。
