<?php

declare(strict_types=1);

use App\Framework\Application\ApplicationInterface;
use App\Framework\Bootstrap\ApplicationBootstrapConfig;
use App\Framework\Http\ExceptionHandling\RethrowingExceptionHandler;
use App\Framework\Http\Middleware\MiddlewarePipeline;
use App\Framework\Security\NativeSessionRuntime;
use App\Kernel\Application;
use App\Kernel\ApplicationDefinition;
use Dotenv\Dotenv;

$root = dirname(__DIR__, 3);
if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$envString = static function (string $key, string $default): string {
    $value = $_ENV[$key] ?? $default;

    return is_scalar($value) ? (string) $value : $default;
};

return static fn (ApplicationBootstrapConfig $config): ApplicationInterface => new Application(
    root: $config->root,
    sessionRuntime: $config->sessionRuntime ?? new NativeSessionRuntime(
        cookieSameSite: $envString(key: 'SESSION_COOKIE_SAMESITE', default: 'Lax'),
        forceSecureCookie: filter_var($envString(key: 'SESSION_COOKIE_SECURE', default: 'false'), FILTER_VALIDATE_BOOL),
    ),
    middlewarePipeline: $config->middlewarePipeline ?? new MiddlewarePipeline(),
    exceptionHandler: $config->exceptionHandler ?? new RethrowingExceptionHandler(),
    definitions: [
        new ApplicationDefinition(
            name: 'cms',
            pathPrefix: '/admin',
            routesPath: '/apps/cms/config/routes.php',
            containerPath: '/apps/cms/config/container.php',
            middlewarePath: '/apps/cms/config/middleware.php',
        ),
        new ApplicationDefinition(
            name: 'front-business',
            pathPrefix: '/business',
            routesPath: '/apps/front-business/config/routes.php',
            containerPath: '/apps/front-business/config/container.php',
            middlewarePath: '/apps/front-business/config/middleware.php',
        ),
        new ApplicationDefinition(
            name: 'front',
            pathPrefix: '/',
            routesPath: '/apps/front/config/routes.php',
            containerPath: '/apps/front/config/container.php',
            middlewarePath: '/apps/front/config/middleware.php',
        ),
    ],
);
