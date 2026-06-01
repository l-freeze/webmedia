<?php

declare(strict_types=1);

namespace Tests\Cms\Http\Middleware;

use App\Cms\Infrastructure\Http\Middleware\RequireCmsSessionMiddleware;
use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\NativeSessionRuntime;
use App\Framework\Security\Session;
use App\Framework\Security\SessionState;
use PHPUnit\Framework\TestCase;

final class RequireCmsSessionMiddlewareTest extends TestCase
{
    public function testAllowsPublicLoginPathWithoutSession(): void
    {
        $middleware = new RequireCmsSessionMiddleware(session: $this->session());

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/admin/login', query: [], body: [], server: []),
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'login'),
            ),
        );

        self::assertSame(200, $response->status);
        self::assertSame('login', $response->body);
    }

    public function testRedirectsAdminRequestWithoutSession(): void
    {
        $middleware = new RequireCmsSessionMiddleware(session: $this->session());

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/admin', query: [], body: [], server: []),
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'admin'),
            ),
        );

        self::assertSame(302, $response->status);
        self::assertSame('/admin/login', $response->headers['Location'] ?? null);
    }

    public function testAllowsAdminRequestWithSession(): void
    {
        $session = $this->session();
        $session->set(key: 'cms.user_id', value: 1);
        $middleware = new RequireCmsSessionMiddleware(session: $session);

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/admin', query: [], body: [], server: []),
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'admin'),
            ),
        );

        self::assertSame(200, $response->status);
        self::assertSame('admin', $response->body);
    }

    private function session(): Session
    {
        $data = [];

        return new Session(state: new SessionState(data: $data), runtime: new NativeSessionRuntime());
    }
}
