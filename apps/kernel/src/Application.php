<?php

declare(strict_types=1);

namespace App\Kernel;

use Pera\Framework\Application\IApplication;
use Pera\Framework\Container\Container;
use Pera\Framework\Http\Exception\MalformedRequestException;
use Pera\Framework\Http\ExceptionHandling\IExceptionHandler;
use Pera\Framework\Http\ExceptionHandling\RethrowingExceptionHandler;
use Pera\Framework\Http\Middleware\CallableRequestHandler;
use Pera\Framework\Http\Middleware\IMiddleware;
use Pera\Framework\Http\Middleware\MiddlewarePipeline;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Routing\Router;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use RuntimeException;
use Throwable;

final class Application implements IApplication
{
    /** @var list<ApplicationDefinition> */
    private array $definitions;

    /** @var array<string, Router> */
    private array $routers = [];

    /** @var array<string, array<int, class-string<IMiddleware>>> */
    private array $middleware = [];

    /**
     * @param list<ApplicationDefinition> $definitions
     */
    public function __construct(
        private readonly string $root,
        private readonly NativeSessionRuntime $sessionRuntime = new NativeSessionRuntime(),
        private readonly MiddlewarePipeline $middlewarePipeline = new MiddlewarePipeline(),
        private readonly IExceptionHandler $exceptionHandler = new RethrowingExceptionHandler(),
        array $definitions = [],
    ) {
        $this->definitions = $this->sortDefinitions(definitions: $definitions);
        foreach ($this->definitions as $definition) {
            $this->routers[$definition->name] = new Router(routes: $this->loadRoutes(path: $definition->routesPath));
            $this->middleware[$definition->name] = $this->loadMiddleware(path: $definition->middlewarePath);
        }
    }

    public function handleGlobals(): void
    {
        $this->handleCurrentRequest()->send();
    }

    private function handleCurrentRequest(): Response
    {
        try {
            $request = Request::fromGlobals();
        } catch (MalformedRequestException) {
            return new Response(
                body: 'Bad Request',
                status: Response::HTTP_BAD_REQUEST,
                headers: ['Content-Type' => 'text/plain; charset=utf-8'],
            );
        }

        $session = Session::fromNativeGlobals(
            runtime: $this->sessionRuntime,
            secure: $request->isHttps(),
        );

        try {
            return $this->handleWithExceptionHandling(request: $request, session: $session);
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
     * @return array<int, class-string<IMiddleware>>
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

        /** @var array<int, class-string<IMiddleware>> $middleware */
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
}
