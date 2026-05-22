<?php

declare(strict_types=1);

namespace App\Framework\Routing;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use FastRoute\Dispatcher;
use function FastRoute\simpleDispatcher;

final readonly class Router
{
    /** @param array<int, array{0:string,1:string,2:class-string|array{0:class-string,1:string}}> $routes */
    public function __construct(
        private Container $container,
        private array $routes,
    ) {
    }

    public function dispatch(Request $request): Response
    {
        $dispatcher = simpleDispatcher(function ($collector): void {
            foreach ($this->routes as [$method, $path, $controller]) {
                $collector->addRoute($method, $path, $controller);
            }
        });

        $result = $dispatcher->dispatch($request->method, $request->path);

        if ($result[0] === Dispatcher::NOT_FOUND) {
            return new Response('Not Found', 404);
        }

        if ($result[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return new Response('Method Not Allowed', 405);
        }

        /** @var class-string|array{0:class-string,1:string} $handler */
        $handler = $result[1];
        /** @var array<string, string> $vars */
        $vars = $result[2];

        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;
            $controller = $this->container->get($controllerClass);
            return $controller->{$method}($request, $vars);
        }

        $controller = $this->container->get($handler);
        return $controller($request, $vars);
    }
}
