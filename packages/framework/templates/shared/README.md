# Shared Application Layer

`apps/shared` contains application-side common services and middleware that are shared by multiple apps.

This scaffold is project setup. Run it once for a project, not once per controller or app.

Framework stays thin: HTTP primitives, routing, container, middleware pipeline, runtime, and bootstrap live in Framework. Application requirements such as security headers and the decision to apply CSRF protection are inserted from project middleware.

The default shared scaffold also includes a Latte implementation of the Framework template renderer interface. Framework defines only the renderer interface; the template engine implementation is application-side shared infrastructure.

The scaffold includes a small form validation set under `App\Shared\Validation`. Validation is application-side because rules, messages, and normalization such as trimming belong to the project, but the generated classes give layered apps a default path.

## Logging

The generated shared container initially binds `Psr\Log\LoggerInterface` to Framework's `PlaceholderLogger`. This is not a real logger. It throws when a log is written so that missing logger configuration does not silently discard application logs.

Replace this binding with the PSR-3 logger used by the application, such as Monolog or a project-specific implementation. If discarding logs is intentional, bind `Psr\Log\NullLogger` explicitly instead.

## Middleware

`apps/shared/config/middleware.php` returns middleware class names that should be applied to every app that imports shared middleware.

`vendor/bin/scaffold-app` generates `apps/{name}/config/middleware.php` so it reads this shared middleware list automatically.

The initial shared list enables security headers and CSRF protection. Safe HTTP methods pass through `CsrfMiddleware` without starting native session storage. Form applications must render the Framework CSRF token as a body field named `_csrf`; the Todo scaffold is a working example.

This browser-safe default keeps a newly generated form protected without requiring policy setup first. If the project later adds a stateless API, comment out or remove `CsrfMiddleware::class` from the shared list and add it explicitly to every browser/session application's `config/middleware.php`. An API that authenticates with cookies or other ambient credentials still requires CSRF protection.

## View

`App\Shared\View\Infrastructure\LatteTemplateRenderer` implements `Pera\Framework\View\ITemplateRenderer`.

Register it in each app container with that app's template directory:

```php
$container->singleton(
    id: ITemplateRenderer::class,
    factory: fn (): ITemplateRenderer => new LatteTemplateRenderer(
        templateDirectory: $root . '/apps/front/templates',
    ),
);
```

Latte is the default starting point, not a Framework requirement. Custom template engines are easy to swap in by implementing `ITemplateRenderer`.

The scaffold adds `latte/latte:^3.0` to the consuming project's runtime requirements when `composer.json` exists. The Framework package itself does not require Latte.

`LatteTemplateRenderer` disables Latte's compiled view cache by default because worker mode keeps loaded template classes in the PHP process. Pass `cacheDirectory` explicitly only when running a classic/non-worker runtime.

To switch an app to Smarty:

1. Add `smarty/smarty` to the project.
2. Create `App\Shared\View\Infrastructure\SmartyTemplateRenderer` that implements `ITemplateRenderer`.
3. Bind `ITemplateRenderer::class` to `SmartyTemplateRenderer` in the target app container.
4. Convert that app's templates from Latte syntax to Smarty syntax.

The Smarty renderer can keep the same `addGlobalProvider()` convenience method used by the default Latte renderer. That keeps common template values, such as site settings, CSRF data, flash messages, or current user information, registered once in the container instead of being passed by every controller.
