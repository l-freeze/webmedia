# Middleware Pipeline

## 目的

HTTP リクエストを Controller へ渡す前後に処理を差し込むための最小パイプラインです。

Framework は `Request -> Middleware -> Controller -> Response` の実行順だけを提供します。認証、request ID付与、監査ログなどの具体的な処理は、利用projectの各AppまたはSharedに置きます。

## Middleware の作り方

`IMiddleware` を実装します。

```php
use Pera\Framework\Http\Middleware\IMiddleware;
use Pera\Framework\Http\Middleware\IRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class ExampleMiddleware implements IMiddleware
{
    public function process(Request $request, IRequestHandler $handler): Response
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

use App\Site\Presentation\Http\Middleware\RequireSignedInUserMiddleware;

return [
    RequireSignedInUserMiddleware::class,
];
```

標準Kernelを使う場合は、Appの`ApplicationDefinition`がそのmiddleware設定fileを指します。

```php
use App\Kernel\ApplicationDefinition;

new ApplicationDefinition(
    name: 'site',
    pathPrefix: '/',
    routesPath: '/apps/site/config/routes.php',
    containerPath: '/apps/site/config/container.php',
    middlewarePath: '/apps/site/config/middleware.php',
);
```

生成KernelはAppごとに指定されたfileからmiddleware listを読みます。独自Kernelを使う場合も、Frameworkの`MiddlewarePipeline`へ同じclass-string listを渡せます。FrameworkはApp名やURL prefixから設定fileを推測しません。

## DI

Middleware は Container から解決されます。コンストラクタで必要なサービスを受け取れます。
