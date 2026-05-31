<?php

declare(strict_types=1);

namespace App\Framework\Http\Middleware;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use RuntimeException;

final readonly class MiddlewarePipeline
{
    /**
     * @param array<int, class-string<MiddlewareInterface>> $middleware
     */
    public function handle(
        Request $request,
        Container $container,
        array $middleware,
        RequestHandlerInterface $destination,
    ): Response {
        $handler = $destination;

        for ($i = count($middleware) - 1; $i >= 0; $i--) {
            $middlewareClass = $middleware[$i];
            $next = $handler;
            $handler = new CallableRequestHandler(
                handler: static function (Request $request) use ($container, $middlewareClass, $next): Response {
                    $instance = $container->get(id: $middlewareClass);
                    if (!$instance instanceof MiddlewareInterface) {
                        throw new RuntimeException("Middleware {$middlewareClass} must implement MiddlewareInterface.");
                    }

                    return $instance->process(request: $request, handler: $next);
                },
            );
        }

        return $handler->handle(request: $request);
    }
}
