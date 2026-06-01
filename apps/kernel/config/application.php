<?php

declare(strict_types=1);

use App\Framework\Application\ApplicationInterface;
use App\Framework\Bootstrap\ApplicationBootstrapConfig;
use App\Framework\Http\ExceptionHandling\RethrowingExceptionHandler;
use App\Framework\Http\Middleware\MiddlewarePipeline;
use App\Framework\Security\NativeSessionRuntime;
use App\Kernel\Application;
use App\Kernel\ApplicationDefinition;

return static fn (ApplicationBootstrapConfig $config): ApplicationInterface => new Application(
    root: $config->root,
    sessionRuntime: $config->sessionRuntime ?? new NativeSessionRuntime(),
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
            name: 'front',
            pathPrefix: '/',
            routesPath: '/apps/front/config/routes.php',
            containerPath: '/apps/front/config/container.php',
            middlewarePath: '/apps/front/config/middleware.php',
        ),
    ],
);
