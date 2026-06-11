# Container

## 目的

Framework の DI container は、アプリケーションの service 解決と constructor injection を扱う最小実装です。

`App\Framework\Container\Container` は PSR-11 `Psr\Container\ContainerInterface` に準拠しています。

## PSR-11

`get()` と `has()` は PSR-11 の contract として公開しています。

```php
use Psr\Container\ContainerInterface;

function boot(ContainerInterface $container): void
{
    $service = $container->get(Service::class);
}
```

例外も PSR-11 の interface を実装しています。

- `ServiceNotFoundException`: `Psr\Container\NotFoundExceptionInterface`
- `ContainerBuildException`: `Psr\Container\ContainerExceptionInterface`

## 独自 API

Framework は PSR-11 の読み取り contract に加えて、定義登録用の薄い API を持ちます。

- `set()`: factory を登録します。
- `singleton()`: 初回解決後に instance を保持する factory を登録します。
- `workerSingleton()`: PHP worker の生存期間だけ instance を保持します。request を跨ぐため、`Request`、`Session`、`Csrf` などの request scope service には使いません。
- `instance()`: 既存 instance を登録します。
- `bind()`: interface や abstract class を concrete class に結びます。

PSR-11 準拠は外部から container を扱うための contract であり、登録 API はこの Framework の composition 用 API です。

## Constructor injection の書き方

Container は constructor の型情報を worker 内で cache します。

DI 対象 class の constructor では、object dependency は default value にせず、必須引数として宣言します。

Good:

```php
final readonly class ArticleService
{
    public function __construct(
        private ArticleRepository $articles,
        private string $status = 'draft',
    ) {
    }
}
```

Bad:

```php
final readonly class ArticleService
{
    public function __construct(
        private ArticleRepository $articles = new InMemoryArticleRepository(),
    ) {
    }
}
```

object default value は worker lifetime の cache に保持される可能性があるため、request 固有状態や mutable object を含めると意図しない共有につながります。
