<?php

declare(strict_types=1);

namespace Tests\Framework;

use Pera\Framework\Http\Middleware\CallableRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\Security\CsrfMiddleware;
use Pera\Framework\Security\NativeSessionRuntime;
use Pera\Framework\Security\Session;
use Pera\Framework\Security\SessionState;
use PHPUnit\Framework\TestCase;

final class CsrfMiddlewareTest extends TestCase
{
    public function testAllowsSafeMethodsWithoutToken(): void
    {
        $middleware = new CsrfMiddleware(csrf: $this->csrf());

        $response = $middleware->process(
            request: new Request(method: 'GET', path: '/contact', query: [], body: [], server: []),
            handler: new CallableRequestHandler(handler: static fn (): Response => new Response(body: 'ok')),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
    }

    public function testRejectsStateChangingRequestWithoutBodyToken(): void
    {
        $middleware = new CsrfMiddleware(csrf: $this->csrf());

        $response = $middleware->process(
            request: new Request(method: 'POST', path: '/contact', query: ['_csrf' => 'query-token'], body: [], server: []),
            handler: new CallableRequestHandler(handler: static fn (): Response => new Response(body: 'ok')),
        );

        self::assertSame(422, $response->status);
        self::assertSame('Invalid CSRF token', $response->body);
    }

    public function testAllowsStateChangingRequestWithValidBodyToken(): void
    {
        $csrf = $this->csrf();
        $token = $csrf->token();
        $middleware = new CsrfMiddleware(csrf: $csrf);

        $response = $middleware->process(
            request: new Request(method: 'POST', path: '/contact', query: [], body: ['_csrf' => $token], server: []),
            handler: new CallableRequestHandler(handler: static fn (): Response => new Response(body: 'ok')),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
    }

    public function testSkipsExcludedPath(): void
    {
        $middleware = new CsrfMiddleware(csrf: $this->csrf(), excludedPaths: ['/webhook/*']);

        $response = $middleware->process(
            request: new Request(method: 'POST', path: '/webhook/github', query: [], body: [], server: []),
            handler: new CallableRequestHandler(handler: static fn (): Response => new Response(body: 'ok')),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
    }

    public function testSkipsWhenDisabledByEnvironmentConfig(): void
    {
        $middleware = new CsrfMiddleware(csrf: $this->csrf(), enabled: false);

        $response = $middleware->process(
            request: new Request(method: 'POST', path: '/contact', query: [], body: [], server: []),
            handler: new CallableRequestHandler(handler: static fn (): Response => new Response(body: 'ok')),
        );

        self::assertSame(200, $response->status);
        self::assertSame('ok', $response->body);
    }

    private function csrf(): Csrf
    {
        $data = [];

        return new Csrf(session: new Session(
            state: new SessionState(data: $data),
            runtime: new NativeSessionRuntime(),
        ));
    }
}
