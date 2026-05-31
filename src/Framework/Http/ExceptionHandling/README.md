# Exception Handling

## 目的

HTTP 処理中に発生した例外を `Response` へ変換するための差し込み口です。

Framework は `ExceptionHandlerInterface` だけを定義し、どの画面を返すか、ログに何を残すか、公開環境で詳細を隠すかはアプリケーション側で決めます。

## ハンドラの作り方

`ExceptionHandlerInterface` を実装します。

```php
use App\Framework\Http\ExceptionHandling\ExceptionHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use Throwable;

final readonly class FrontExceptionHandler implements ExceptionHandlerInterface
{
    public function handle(Throwable $throwable, Request $request): Response
    {
        return new Response(
            body: 'Internal Server Error',
            status: 500,
            headers: ['Content-Type' => 'text/plain; charset=utf-8'],
        );
    }
}
```

## 差し込み方

`Application` のコンストラクタに渡します。

```php
$application = new Application(
    root: dirname(__DIR__),
    exceptionHandler: new FrontExceptionHandler(),
);
```

デフォルトは `RethrowingExceptionHandler` です。これは従来挙動を保つため、受け取った例外をそのまま再 throw します。

## 用意済みハンドラ

- `RethrowingExceptionHandler`: 例外を再 throw するデフォルト実装。
- `PlainTextExceptionHandler`: 500 の plain text response を返す最小実装。
