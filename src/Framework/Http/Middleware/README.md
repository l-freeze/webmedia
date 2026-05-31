# Middleware Pipeline

## 目的

HTTP リクエストを Controller へ渡す前後に処理を差し込むための最小パイプラインです。

Framework は `Request -> Middleware -> Controller -> Response` の実行順だけを提供します。認証、request id 付与、監査ログなどの具体的な処理は `apps/cms`、`apps/front`、または `apps/shared` に置きます。

## Middleware の作り方

`MiddlewareInterface` を実装します。

```php
use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class ExampleMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $response = $handler->handle(request: $request);

        return $response->withHeader(name: 'X-Example', value: '1');
    }
}
```

後続の Controller を呼ばずに `Response` を返すと、そこで処理を終了できます。

## 差し込み方

アプリケーションごとに `config/middleware.php` を作り、実行したい順に class-string を返します。

```php
<?php

declare(strict_types=1);

use App\Cms\Infrastructure\Http\Middleware\RequireAdminSessionMiddleware;

return [
    RequireAdminSessionMiddleware::class,
];
```

`Application` は `/admin` では `apps/cms/config/middleware.php`、それ以外では `apps/front/config/middleware.php` を読みます。ファイルがない場合は middleware なしとして扱います。

## DI

Middleware は Container から解決されます。コンストラクタで必要なサービスを受け取れます。
