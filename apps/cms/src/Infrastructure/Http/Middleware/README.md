# CMS Middleware

CMS 専用の middleware はこのディレクトリへ置きます。

Framework の middleware pipeline は実行順と呼び出し規約だけを提供します。CMS のログイン必須判定、CMS 固有の監査、CMS 固有の request 制約のように、アプリケーションの事情を知る処理は `apps/cms` 側で実装します。

現時点では CMS 固有 middleware を `Infrastructure/Http/Middleware` に統一します。HTTP pipeline に差し込む基盤部品として扱うことで、Controller の置き場所である `Presentation/Http/Controller` と役割を分けます。

## 将来の分離ルール

middleware が増え、1つのディレクトリに置くとかえって責務が読みにくくなった場合だけ、次の基準で分離します。

- `apps/cms/src/Presentation/Http/Middleware/`: 画面、ルート、ログイン状態、表示モード、SEO、redirect など、CMS の HTTP 表現に直接関わる middleware。
- `apps/cms/src/Infrastructure/Http/Middleware/`: session adapter、proxy header、upload limit、監査ログ送出、外部基盤都合の request/response 制約など、技術的な入出力や実行基盤に近い middleware。
- `src/Framework/Http/Middleware/`: CMS/Front に依存しない middleware interface、pipeline、共通実行機構。

TODO: middleware が複数増えた時点で、この基準に照らして配置を見直します。分離する場合は class の namespace、`apps/cms/config/middleware.php` の import、README の配置説明を同じ変更単位で更新します。

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
use App\Cms\Infrastructure\Http\Middleware\CmsAuditMiddleware;
use App\Cms\Infrastructure\Http\Middleware\RequireCmsPermissionMiddleware;
use App\Cms\Infrastructure\Http\Middleware\RequireCmsSessionMiddleware;

return [
    ...$sharedMiddleware,
    RequireCmsSessionMiddleware::class,
    RequireCmsPermissionMiddleware::class,
    CmsAuditMiddleware::class,
];
```

Middleware は Container から解決されるため、コンストラクタで `Session` などの依存を受け取れます。

CMS の標準順序は次の通りです。

```text
RequireCmsSessionMiddleware
  -> RequireCmsPermissionMiddleware
  -> CmsAuditMiddleware
  -> Controller
```

この順序にする理由:

- session middleware が先に未ログイン request を止める。
- permission middleware がログイン済みユーザーの大まかな route 権限を止める。
- audit middleware は認証・認可を通過した CMS 状態変更 request の HTTP envelope を記録する。

## Middleware 別 README

- [RequireCmsSessionMiddleware](./RequireCmsSessionMiddleware.README.md)
- [RequireCmsPermissionMiddleware](./RequireCmsPermissionMiddleware.README.md)
- [CmsAuditMiddleware](./CmsAuditMiddleware.README.md)
