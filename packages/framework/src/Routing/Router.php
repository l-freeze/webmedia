<?php

declare(strict_types=1);

namespace Pera\Framework\Routing;

use Pera\Framework\Container\Container;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use RuntimeException;
use function FastRoute\simpleDispatcher;

final class Router
{
    public const ROUTE_METADATA_ATTRIBUTE = 'route.metadata';

    private ?Dispatcher $dispatcher = null;

    /**
     * @param array<int, array{0:string,1:string,2:class-string|array{0:class-string,1:string},3?:array<string, mixed>}> $routes
     */
    public function __construct(
        private array $routes,
    ) {
    }

    public function withRouteMetadata(Request $request): Request
    {
        $result = $this->dispatcher()->dispatch($request->method, $request->path);
        if ($result[0] !== Dispatcher::FOUND) {
            return $request;
        }

        /** @var array{controller: class-string|array{0:class-string,1:string}, metadata: array<string, mixed>} $route */
        $route = $result[1];

        return $request->withAttribute(key: self::ROUTE_METADATA_ATTRIBUTE, value: $route['metadata']);
    }

    public function dispatch(Request $request, Container $container): Response
    {
        $result = $this->dispatcher()->dispatch($request->method, $request->path);

        if ($result[0] === Dispatcher::NOT_FOUND) {
            return new Response(body: 'Not Found', status: Response::HTTP_NOT_FOUND);
        }

        if ($result[0] === Dispatcher::METHOD_NOT_ALLOWED) {
            /** @var array<int, string> $allowedMethods */
            $allowedMethods = $result[1];

            return new Response(
                body: 'Method Not Allowed',
                status: Response::HTTP_METHOD_NOT_ALLOWED,
                headers: [
                    'Content-Type' => 'text/html; charset=utf-8',
                    'Allow' => implode(', ', $allowedMethods),
                ],
            );
        }

        /** @var array{controller: class-string|array{0:class-string,1:string}, metadata: array<string, mixed>} $route */
        $route = $result[1];
        $handler = $route['controller'];
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
            foreach ($this->routes as $route) {
                [$method, $path, $controller] = $route;
                $metadata = $route[3] ?? [];
                $collector->addRoute($method, $path, [
                    'controller' => $controller,
                    'metadata' => $metadata,
                ]);
            }
        });
    }
}
