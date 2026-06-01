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

## 差し込み方: bootstrap から使う場合

通常のエントリポイントでは `ApplicationBootstrapConfig` に渡します。

```php
use App\Framework\Bootstrap\ApplicationBootstrap;
use App\Framework\Bootstrap\ApplicationBootstrapConfig;

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

$applicationFactory = require $root . '/apps/kernel/config/application.php';

ApplicationBootstrap::run(
    config: new ApplicationBootstrapConfig(
        root: $root,
        applicationFactory: $applicationFactory,
        exceptionHandler: new FrontExceptionHandler(),
    ),
);
```

`public/index.php` は `ApplicationBootstrap::run()` を呼ぶだけにし、環境ごとに返したいエラー画面やログ出力が変わる場合は、ここで渡すハンドラを差し替えます。

## 差し込み方: Kernel Application を直接作る場合

プロジェクト側の `App\Kernel\Application` のコンストラクタに渡します。

```php
use App\Kernel\Application;

$application = new Application(
    root: dirname(__DIR__),
    exceptionHandler: new FrontExceptionHandler(),
);
```

これは主にテストや bootstrap を経由しない実行で使います。

## デフォルト挙動

デフォルトは `RethrowingExceptionHandler` です。これは従来挙動を保つため、受け取った例外をそのまま再 throw します。

未処理例外を 500 response に変換したい場合は、`PlainTextExceptionHandler` またはアプリケーション固有の `ExceptionHandlerInterface` 実装を `ApplicationBootstrapConfig` に渡してください。

```php
use App\Framework\Bootstrap\ApplicationBootstrapConfig;
use App\Framework\Http\ExceptionHandling\PlainTextExceptionHandler;

new ApplicationBootstrapConfig(
    root: dirname(__DIR__),
    applicationFactory: $applicationFactory,
    exceptionHandler: new PlainTextExceptionHandler(),
);
```

## 用意済みハンドラ

- `RethrowingExceptionHandler`: 例外を再 throw するデフォルト実装。
- `PlainTextExceptionHandler`: 500 の plain text response を返す最小実装。
