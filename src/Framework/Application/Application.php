<?php

declare(strict_types=1);

namespace App\Framework\Application;

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

final class Application
{
    private Router $cmsRouter;

    private Router $frontRouter;

    /** @var array<int, class-string<MiddlewareInterface>> */
    private array $cmsMiddleware;

    /** @var array<int, class-string<MiddlewareInterface>> */
    private array $frontMiddleware;

    public function __construct(
        private readonly string $root,
        private readonly NativeSessionRuntime $sessionRuntime = new NativeSessionRuntime(),
        private readonly MiddlewarePipeline $middlewarePipeline = new MiddlewarePipeline(),
        private readonly ExceptionHandlerInterface $exceptionHandler = new RethrowingExceptionHandler(),
    ) {
        $this->cmsRouter = new Router(routes: $this->loadRoutes(path: '/apps/cms/config/routes.php'));
        $this->frontRouter = new Router(routes: $this->loadRoutes(path: '/apps/front/config/routes.php'));
        $this->cmsMiddleware = $this->loadMiddleware(path: '/apps/cms/config/middleware.php');
        $this->frontMiddleware = $this->loadMiddleware(path: '/apps/front/config/middleware.php');
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

    /** @param array<string, string> $cookies */
    public function handleWorkerRequest(Request $request, array $cookies): Response
    {
        $_COOKIE = $cookies;
        $this->sessionRuntime->start(secure: $request->isHttps());

        try {
            /** @var array<string, mixed> $_SESSION */
            $sessionData = &$_SESSION;
            $session = new Session(
                state: new SessionState(data: $sessionData),
                runtime: $this->sessionRuntime,
            );

            $response = $this->handleWithExceptionHandling(request: $request, session: $session);
            $cookieHeader = $this->sessionRuntime->cookieHeader();

            return $cookieHeader === null ? $response : $response->withHeader(name: 'Set-Cookie', value: $cookieHeader);
        } finally {
            $this->sessionRuntime->close();
        }
    }

    public function handle(Request $request, Session $session): Response
    {
        $isCms = str_starts_with($request->path, '/admin');
        $container = $this->loadContainer(path: $isCms ? '/apps/cms/config/container.php' : '/apps/front/config/container.php');
        $container->instance(id: Request::class, instance: $request);
        $container->instance(id: Session::class, instance: $session);

        $router = $isCms ? $this->cmsRouter : $this->frontRouter;

        return $this->middlewarePipeline->handle(
            request: $request,
            container: $container,
            middleware: $isCms ? $this->cmsMiddleware : $this->frontMiddleware,
            destination: new CallableRequestHandler(
                handler: static fn (Request $request): Response => $router->dispatch(
                    request: $request,
                    container: $container,
                ),
            ),
        );
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
     * @return array<int, array{0:string,1:string,2:array{0:class-string,1:string}|class-string}>
     */
    private function loadRoutes(string $path): array
    {
        $routes = require $this->root . $path;
        if (!is_array($routes)) {
            throw new RuntimeException('Application route configuration is invalid.');
        }

        /** @var array<int, array{0:string,1:string,2:array{0:class-string,1:string}|class-string}> $routes */
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
}
