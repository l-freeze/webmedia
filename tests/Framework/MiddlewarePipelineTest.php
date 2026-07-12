<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Container\Container;
use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Middleware\IMiddleware;
use App\Framework\Http\Middleware\MiddlewarePipeline;
use App\Framework\Http\Middleware\IRequestHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use PHPUnit\Framework\TestCase;

final class MiddlewarePipelineTest extends TestCase
{
    public function testRunsMiddlewareInConfiguredOrder(): void
    {
        $pipeline = new MiddlewarePipeline();
        $container = new Container();
        $request = new Request(method: 'GET', path: '/', query: [], body: [], server: []);

        $response = $pipeline->handle(
            request: $request,
            container: $container,
            middleware: [
                FirstMiddleware::class,
                SecondMiddleware::class,
            ],
            destination: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'controller'),
            ),
        );

        self::assertSame('first-second-controller-second-first', $response->body);
    }

    public function testMiddlewareCanShortCircuitRequest(): void
    {
        $pipeline = new MiddlewarePipeline();
        $container = new Container();
        $request = new Request(method: 'GET', path: '/', query: [], body: [], server: []);

        $response = $pipeline->handle(
            request: $request,
            container: $container,
            middleware: [
                ShortCircuitMiddleware::class,
                SecondMiddleware::class,
            ],
            destination: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'controller'),
            ),
        );

        self::assertSame(403, $response->status);
        self::assertSame('blocked', $response->body);
    }
}

final readonly class FirstMiddleware implements IMiddleware
{
    public function process(Request $request, IRequestHandler $handler): Response
    {
        $response = $handler->handle(request: $request);

        return new Response(body: 'first-' . $response->body . '-first', status: $response->status);
    }
}

final readonly class SecondMiddleware implements IMiddleware
{
    public function process(Request $request, IRequestHandler $handler): Response
    {
        $response = $handler->handle(request: $request);

        return new Response(body: 'second-' . $response->body . '-second', status: $response->status);
    }
}

final readonly class ShortCircuitMiddleware implements IMiddleware
{
    public function process(Request $request, IRequestHandler $handler): Response
    {
        return new Response(body: 'blocked', status: 403);
    }
}
