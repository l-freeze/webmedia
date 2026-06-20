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

## CmsAuditMiddleware

`CmsAuditMiddleware` は CMS の監査対象 request から `AuditEvent` を作り、`AuditLoggerInterface` に渡します。

対象は `/admin` 配下の状態変更 method です。

```text
POST
PUT
PATCH
DELETE
```

対象判定は allowlist ではなく denylist で行います。CMS の操作は増えるため、新規操作を監査対象に入れ忘れないことを優先します。監査ログが不要な path だけを denylist に追加します。

初期 denylist は以下です。

```text
/admin/login
/admin/logout
```

GET は初期対象外です。問い合わせ詳細閲覧、エクスポート、ダウンロードなど、GET でも監査が必要な操作が出た場合だけ個別に設計します。

`CmsAuditMiddleware` は AuditLogger の例外を握りつぶしません。`PlaceholderAuditLogger` のまま監査対象 request を実行した場合に例外が見えることは、Audit 未設定を検知するための意図した挙動です。

実運用の AuditLogger 実装では、HTTP 監査ログの保存失敗だけで完了済み CMS 操作の response を壊さないことを標準にします。保存失敗は通常ログや通知経路へ記録します。監査ログ保存を業務処理と同じ成否条件にしたい場合は、middleware ではなく対象 UseCase / Controller の Business Audit と永続化処理を同じ transaction 境界で設計します。

IP address は `Request::$server['REMOTE_ADDR']` を基本にします。trusted proxy 設計を導入するまで、`X-Forwarded-For` などの forwarded header を無条件に信用してはいけません。

永続化先、DB schema、`AuditEvent` から保存形式への mapping は AuditLogger 実装側の責務です。middleware は request body 全体、password、CSRF token、cookie、session id を保存しません。
