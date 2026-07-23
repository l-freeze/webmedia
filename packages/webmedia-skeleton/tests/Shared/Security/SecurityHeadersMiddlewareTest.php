<?php

declare(strict_types=1);

namespace Tests\Shared\Security;

use Pera\Framework\Http\Middleware\CallableRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use App\Shared\Security\Infrastructure\Http\SecurityHeadersMiddleware;
use PHPUnit\Framework\TestCase;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testAddsSecurityHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request(
            method: 'GET',
            path: '/',
            query: [],
            body: [],
            server: [],
        );

        $response = $middleware->process(
            request: $request,
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'ok'),
            ),
        );

        self::assertSame('ok', $response->body);
        self::assertSame('nosniff', $response->headers['X-Content-Type-Options'] ?? null);
        self::assertSame('DENY', $response->headers['X-Frame-Options'] ?? null);
        self::assertArrayHasKey('Content-Security-Policy', $response->headers);
    }

    public function testDoesNotOverwriteExplicitHeaders(): void
    {
        $middleware = new SecurityHeadersMiddleware();
        $request = new Request(
            method: 'GET',
            path: '/',
            query: [],
            body: [],
            server: [],
        );

        $response = $middleware->process(
            request: $request,
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(
                    body: 'ok',
                    headers: [
                        'Content-Type' => 'text/html; charset=utf-8',
                        'X-Frame-Options' => 'SAMEORIGIN',
                    ],
                ),
            ),
        );

        self::assertSame('SAMEORIGIN', $response->headers['X-Frame-Options'] ?? null);
    }
}
