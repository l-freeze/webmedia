# CMS Middleware

CMS 専用の middleware はこのディレクトリへ置きます。

Framework の middleware pipeline は実行順と呼び出し規約だけを提供します。CMS のログイン必須判定、CMS 固有の監査、CMS 固有の request 制約のように、アプリケーションの事情を知る処理は `apps/cms` 側で実装します。

## 作り方

`App\Framework\Http\Middleware\MiddlewareInterface` を実装します。

```php
use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class ExampleMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if ($this->shouldStop($request)) {
            return Response::redirect(location: '/admin/login');
        }

        return $handler->handle(request: $request);
    }
}
```

処理を続ける場合は `$handler->handle(request: $request)` を呼びます。そこで止める場合は `Response` を返します。

## 登録方法

`apps/cms/config/middleware.php` に class-string を実行したい順に追加します。

```php
use App\Cms\Infrastructure\Http\Middleware\RequireCmsSessionMiddleware;

return [
    ...$sharedMiddleware,
    RequireCmsSessionMiddleware::class,
];
```

Middleware は Container から解決されるため、コンストラクタで `Session` などの依存を受け取れます。

## RequireCmsSessionMiddleware

`RequireCmsSessionMiddleware` は CMS 管理画面にログイン済みセッションを要求します。

`/admin/login` はログインフォーム表示とログイン処理のため public path として通します。それ以外の CMS リクエストで `cms.user_id` が Session に存在しない場合は `/admin/login` へ redirect します。
