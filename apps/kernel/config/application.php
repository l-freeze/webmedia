<?php

declare(strict_types=1);

use Pera\Framework\Application\IApplication;
use Pera\Framework\Bootstrap\ApplicationBootstrapConfig;
use Pera\Framework\Http\ExceptionHandling\RethrowingExceptionHandler;
use Pera\Framework\Http\Middleware\MiddlewarePipeline;
use Pera\Framework\Security\NativeSessionRuntime;
use App\Kernel\Application;
use App\Kernel\ApplicationDefinition;
use App\Kernel\Environment;
use Dotenv\Dotenv;

$root = dirname(__DIR__, 3);
if (is_file($root . '/.env')) {
    Dotenv::createImmutable($root)->safeLoad();
}

$environment = new Environment();

return static fn (ApplicationBootstrapConfig $config): IApplication => new Application(
    root: $config->root,
    sessionRuntime: $config->sessionRuntime ?? new NativeSessionRuntime(
        cookieSameSite: $environment->string(key: 'SESSION_COOKIE_SAMESITE', default: 'Lax'),
        forceSecureCookie: filter_var(
            $environment->string(key: 'SESSION_COOKIE_SECURE', default: 'false'),
            FILTER_VALIDATE_BOOL,
        ),
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
