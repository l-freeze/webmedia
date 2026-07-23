<?php

declare(strict_types=1);

use Pera\Framework\Application\IApplication;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;
use Pera\Framework\Http\ExceptionHandling\RethrowingExceptionHandler;
use Pera\Framework\Http\Middleware\MiddlewarePipeline;
use Pera\Framework\Security\NativeSessionRuntime;
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

return static fn (ApplicationBootstrapConfig $config): IApplication => new Application(
    root: $config->root,
    sessionRuntime: $config->sessionRuntime ?? new NativeSessionRuntime(
        cookieSameSite: $envString(key: 'SESSION_COOKIE_SAMESITE', default: 'Lax'),
        forceSecureCookie: filter_var($envString(key: 'SESSION_COOKIE_SECURE', default: 'false'), FILTER_VALIDATE_BOOL),
    ),
    middlewarePipeline: $config->middlewarePipeline ?? new MiddlewarePipeline(),
    exceptionHandler: $config->exceptionHandler ?? new RethrowingExceptionHandler(),
    definitions: [
        new ApplicationDefinition(
            name: 'minimal',
            pathPrefix: '/',
            routesPath: '/apps/minimal/config/routes.php',
            containerPath: '/apps/minimal/config/container.php',
            middlewarePath: '/apps/minimal/config/middleware.php',
        ),
    ],
);
