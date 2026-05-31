<?php

declare(strict_types=1);

namespace App\Framework\Routing;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use RuntimeException;
use function FastRoute\simpleDispatcher;

final class Router
{
    private ?Dispatcher $dispatcher = null;

    /** @param array<int, array{0:string,1:string,2:class-string|array{0:class-string,1:string}}> $routes */
    public function __construct(
        private array $routes,
    ) {
    }

    public function dispatch(Request $request, Container $container): Response
    {
        $result = $this->dispatcher()->dispatch($request->method, $request->path);

        if ($result[0] === Dispatcher::NOT_FOUND) {
            return new Response(body: 'Not Found', status: 404);
        }

        if ($result[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            return new Response(body: 'Method Not Allowed', status: 405);
        }

        /** @var class-string|array{0:class-string,1:string} $handler */
        $handler = $result[1];
        /** @var array<string, string> $vars */
        $vars = $result[2];

        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;
            $controller = $container->get(id: $controllerClass);
            if (!is_object($controller) || !method_exists($controller, $method)) {
                throw new RuntimeException("Controller {$controllerClass}::{$method} is not callable.");
            }

            $response = $controller->{$method}($request, $vars);
            if (!$response instanceof Response) {
                throw new RuntimeException("Controller {$controllerClass}::{$method} did not return a response.");
            }

            return $response;
        }

        $controller = $container->get(id: $handler);
        if (!is_callable($controller)) {
            throw new RuntimeException("Controller {$handler} is not callable.");
        }

        $response = $controller($request, $vars);
        if (!$response instanceof Response) {
            throw new RuntimeException("Controller {$handler} did not return a response.");
        }

        return $response;
    }

    private function dispatcher(): Dispatcher
    {
        return $this->dispatcher ??= simpleDispatcher(function (RouteCollector $collector): void {
            foreach ($this->routes as [$method, $path, $controller]) {
                $collector->addRoute($method, $path, $controller);
            }
        });
    }
}
