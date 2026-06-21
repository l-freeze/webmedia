<?php

declare(strict_types=1);

namespace App\Kernel;

use App\Framework\Application\ApplicationInterface;
use App\Framework\Container\Container;
use App\Framework\Http\ExceptionHandling\ExceptionHandlerInterface;
use App\Framework\Http\ExceptionHandling\RethrowingExceptionHandler;
use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\MiddlewarePipeline;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Router;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use RuntimeException;
use Throwable;

final class Application implements ApplicationInterface
{
    /** @var list<ApplicationDefinition> */
    private array $definitions;

    /** @var array<string, Router> */
    private array $routers = [];

    /** @var array<string, array<int, class-string<MiddlewareInterface>>> */
    private array $middleware = [];

    /**
     * @param list<ApplicationDefinition> $definitions
     */
    public function __construct(
        private readonly string $root,
        private readonly NativeSessionRuntime $sessionRuntime = new NativeSessionRuntime(),
        private readonly MiddlewarePipeline $middlewarePipeline = new MiddlewarePipeline(),
        private readonly ExceptionHandlerInterface $exceptionHandler = new RethrowingExceptionHandler(),
        array $definitions = [],
    ) {
        $this->definitions = $definitions !== [] ? $this->sortDefinitions(definitions: $definitions) : $this->defaultDefinitions();
        foreach ($this->definitions as $definition) {
            $this->routers[$definition->name] = new Router(routes: $this->loadRoutes(path: $definition->routesPath));
            $this->middleware[$definition->name] = $this->loadMiddleware(path: $definition->middlewarePath);
        }
    }

    public function handleGlobals(): void
    {
        $request = Request::fromGlobals();
        $this->sessionRuntime->start(secure: $request->isHttps());

        try {
            /** @var array<string, mixed> $_SESSION */
            $sessionData = &$_SESSION;
            $session = new Session(
                state: new SessionState(data: $sessionData),
                runtime: $this->sessionRuntime,
            );

            $this->handleWithExceptionHandling(request: $request, session: $session)->send();
        } finally {
            $this->sessionRuntime->close();
        }
    }

    public function handle(Request $request, Session $session): Response
    {
        $definition = $this->matchDefinition(path: $request->path);
        $container = $this->loadContainer(path: $definition->containerPath);
        $container->instance(id: Request::class, instance: $request);
        $container->instance(id: Session::class, instance: $session);

        $router = $this->routers[$definition->name] ?? throw new RuntimeException("Application router is not loaded: {$definition->name}");
        $request = $router->withRouteMetadata(request: $request);

        return $this->middlewarePipeline->handle(
            request: $request,
            container: $container,
            middleware: $this->middleware[$definition->name] ?? [],
            destination: new CallableRequestHandler(
                handler: static fn (Request $request): Response => $router->dispatch(
                    request: $request,
                    container: $container,
                ),
            ),
        );
    }

    private function matchDefinition(string $path): ApplicationDefinition
    {
        foreach ($this->definitions as $definition) {
            if ($definition->pathPrefix === '/' || $path === $definition->pathPrefix || str_starts_with($path, $definition->pathPrefix . '/')) {
                return $definition;
            }
        }

        throw new RuntimeException("No application definition matched path: {$path}");
    }

    private function handleWithExceptionHandling(Request $request, Session $session): Response
    {
        try {
            return $this->handle(request: $request, session: $session);
        } catch (Throwable $throwable) {
            return $this->exceptionHandler->handle(throwable: $throwable, request: $request);
        }
    }

    private function loadContainer(string $path): Container
    {
        $container = require $this->root . $path;
        if (!$container instanceof Container) {
            throw new RuntimeException('Application container configuration is invalid.');
        }

        return $container;
    }

    /**
     * @return array<int, array{0:string,1:string,2:array{0:class-string,1:string}|class-string,3?:array<string, mixed>}>
     */
    private function loadRoutes(string $path): array
    {
        $routes = require $this->root . $path;
        if (!is_array($routes)) {
            throw new RuntimeException('Application route configuration is invalid.');
        }

        /** @var array<int, array{0:string,1:string,2:array{0:class-string,1:string}|class-string,3?:array<string, mixed>}> $routes */
        return $routes;
    }

    /**
     * @return array<int, class-string<MiddlewareInterface>>
     */
    private function loadMiddleware(string $path): array
    {
        $fullPath = $this->root . $path;
        if (!is_file($fullPath)) {
            return [];
        }

        $middleware = require $fullPath;
        if (!is_array($middleware)) {
            throw new RuntimeException('Application middleware configuration is invalid.');
        }

        /** @var array<int, class-string<MiddlewareInterface>> $middleware */
        return $middleware;
    }

    /**
     * @param list<ApplicationDefinition> $definitions
     * @return list<ApplicationDefinition>
     */
    private function sortDefinitions(array $definitions): array
    {
        usort(
            $definitions,
            static fn (ApplicationDefinition $a, ApplicationDefinition $b): int => strlen($b->pathPrefix) <=> strlen($a->pathPrefix),
        );

        return $definitions;
    }

    /** @return list<ApplicationDefinition> */
    private function defaultDefinitions(): array
    {
        return $this->sortDefinitions(definitions: [
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
        ]);
    }
}
