<?php

declare(strict_types=1);

namespace App\Framework\Application;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Router;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use RuntimeException;

final class Application
{
    private Router $cmsRouter;

    private Router $frontRouter;

    public function __construct(
        private readonly string $root,
        private readonly NativeSessionRuntime $sessionRuntime = new NativeSessionRuntime(),
    ) {
        $this->cmsRouter = new Router(routes: $this->loadRoutes(path: '/apps/cms/config/routes.php'));
        $this->frontRouter = new Router(routes: $this->loadRoutes(path: '/apps/front/config/routes.php'));
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

            $this->handle(request: $request, session: $session)->send();
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

            $response = $this->handle(request: $request, session: $session);
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

        return ($isCms ? $this->cmsRouter : $this->frontRouter)->dispatch(
            request: $request,
            container: $container,
        );
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
}
