<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Container\Container;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testDispatchesInvokableControllerWithRequestContainer(): void
    {
        $router = new Router(routes: [
            ['GET', '/hello', InvokableController::class],
        ]);

        $response = $router->dispatch(
            request: new Request(method: 'GET', path: '/hello', query: [], body: [], server: []),
            container: new Container(),
        );

        self::assertSame(200, $response->status);
        self::assertSame('hello', $response->body);
    }

    public function testReturnsNotFoundResponse(): void
    {
        $router = new Router(routes: []);

        $response = $router->dispatch(
            request: new Request(method: 'GET', path: '/missing', query: [], body: [], server: []),
            container: new Container(),
        );

        self::assertSame(404, $response->status);
    }

    public function testAddsMatchedRouteMetadataToRequestAttributes(): void
    {
        $router = new Router(routes: [
            ['POST', '/admin/articles/{id:\d+}', InvokableController::class, ['feature' => 'enabled']],
        ]);

        $request = $router->withRouteMetadata(
            request: new Request(method: 'POST', path: '/admin/articles/10', query: [], body: [], server: []),
        );

        self::assertSame(
            ['feature' => 'enabled'],
            $request->attribute(key: Router::ROUTE_METADATA_ATTRIBUTE),
        );
    }

    public function testLeavesRequestUnchangedWhenNoRouteMatchesForMetadata(): void
    {
        $router = new Router(routes: []);
        $request = new Request(method: 'GET', path: '/missing', query: [], body: [], server: []);

        self::assertSame($request, $router->withRouteMetadata(request: $request));
    }
}

final readonly class InvokableController
{
    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars): Response
    {
        return new Response(body: 'hello');
    }
}
