# CMS Middleware

CMS 専用の middleware はこのディレクトリへ置きます。

Framework の middleware pipeline は実行順と呼び出し規約だけを提供します。CMS のログイン必須判定、CMS 固有の監査、CMS 固有の request 制約のように、アプリケーションの事情を知る処理は `apps/cms` 側で実装します。

現時点では CMS 固有 middleware を `Presentation/Http/Middleware` に統一します。理由は、現在の CMS middleware がすべて「CMS の HTTP request/response をどう扱うか」という表示層入口の制御であり、DB、ファイル、外部I/Oなどの Infrastructure 詳細ではないためです。

## 将来の分離ルール

middleware が増え、1つのディレクトリに置くとかえって責務が読みにくくなった場合だけ、次の基準で分離します。

- `apps/cms/src/Presentation/Http/Middleware/`: 画面、ルート、ログイン状態、表示モード、SEO、redirect、CMS 固有の認証/認可入口、CMS 固有の HTTP 監査 event 発火など、CMS の HTTP 表現に直接関わる middleware。
- `apps/cms/src/Infrastructure/Http/Middleware/`: proxy header、upload limit、外部基盤都合の request/response 制約など、技術的な入出力や実行基盤に近い middleware が必要になった場合だけ作る。
- `packages/framework/src/Http/Middleware/`: CMS/Front に依存しない middleware interface、pipeline、共通実行機構。

現在 `apps/cms/src/Infrastructure/Http` は使いません。必要な責務が出るまで作らない方針です。

## 作り方

`Pera\Framework\Http\Middleware\IMiddleware` を実装します。

```php
use Pera\Framework\Http\Middleware\IMiddleware;
use Pera\Framework\Http\Middleware\IRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class ExampleMiddleware implements IMiddleware
{
    public function process(Request $request, IRequestHandler $handler): Response
    {
        if ($this->shouldStop($request)) {
            return Response::redirect('/admin/login');
        }

        return $handler->handle(request: $request);
    }
}
```

処理を続ける場合は `$handler->handle(request: $request)` を呼びます。そこで止める場合は `Response` を返します。

## 登録方法

`apps/cms/config/middleware.php` に class-string を実行したい順に追加します。

```php
use App\Cms\Presentation\Http\Middleware\AuditMiddleware;
use App\Cms\Presentation\Http\Middleware\RequireCmsPermissionMiddleware;
use App\Cms\Presentation\Http\Middleware\RequireCmsSessionMiddleware;

return [
    ...$sharedMiddleware,
    RequireCmsSessionMiddleware::class,
    RequireCmsPermissionMiddleware::class,
    AuditMiddleware::class,
];
```

Middleware は Container から解決されるため、コンストラクタで `Session` などの依存を受け取れます。

CMS の標準順序は次の通りです。

```text
RequireCmsSessionMiddleware
  -> RequireCmsPermissionMiddleware
  -> AuditMiddleware
  -> Controller
```

この順序にする理由:

- session middleware が先に未ログイン request を止める。
- permission middleware がログイン済みユーザーの大まかな route 権限を止める。
- audit middleware は認証・認可を通過した CMS 状態変更 request の HTTP envelope を記録する。

## Middleware 別 README

- [RequireCmsSessionMiddleware](./RequireCmsSessionMiddleware.README.md)
- [RequireCmsPermissionMiddleware](./RequireCmsPermissionMiddleware.README.md)
- [AuditMiddleware](./AuditMiddleware.README.md)
