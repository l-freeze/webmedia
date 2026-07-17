<?php

declare(strict_types=1);

namespace Pera\Framework\Http\Middleware;

use Pera\Framework\Container\Container;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use RuntimeException;

final readonly class MiddlewarePipeline
{
    /**
     * @param array<int, class-string<IMiddleware>> $middleware
     */
    public function handle(
        Request $request,
        Container $container,
        array $middleware,
        IRequestHandler $destination,
    ): Response {
        $handler = $destination;

        for ($i = count($middleware) - 1; $i >= 0; $i--) {
            $middlewareClass = $middleware[$i];
            $next = $handler;
            $handler = new CallableRequestHandler(
                handler: static function (Request $request) use ($container, $middlewareClass, $next): Response {
                    $instance = $container->get(id: $middlewareClass);
                    if (!$instance instanceof IMiddleware) {
                        throw new RuntimeException("Middleware {$middlewareClass} must implement IMiddleware.");
                    }

                    return $instance->process(request: $request, handler: $next);
                },
            );
        }

        return $handler->handle(request: $request);
    }
}
