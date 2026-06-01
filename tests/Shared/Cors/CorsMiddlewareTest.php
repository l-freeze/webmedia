<?php

declare(strict_types=1);

namespace Tests\Shared\Cors;

use App\Framework\Http\Middleware\CallableRequestHandler;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Shared\Cors\Infrastructure\Http\CorsMiddleware;
use PHPUnit\Framework\TestCase;

final class CorsMiddlewareTest extends TestCase
{
    public function testAddsCorsHeadersForAllowedOrigin(): void
    {
        $middleware = new CorsMiddleware(allowedOrigins: ['https://example.com']);
        $request = new Request(
            method: 'GET',
            path: '/',
            query: [],
            body: [],
            server: ['HTTP_ORIGIN' => 'https://example.com'],
        );

        $response = $middleware->process(
            request: $request,
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'ok'),
            ),
        );

        self::assertSame('ok', $response->body);
        self::assertSame('https://example.com', $response->headers['Access-Control-Allow-Origin'] ?? null);
        self::assertSame('Origin', $response->headers['Vary'] ?? null);
    }

    public function testDoesNotAddCorsHeadersForDisallowedOrigin(): void
    {
        $middleware = new CorsMiddleware(allowedOrigins: ['https://example.com']);
        $request = new Request(
            method: 'GET',
            path: '/',
            query: [],
            body: [],
            server: ['HTTP_ORIGIN' => 'https://invalid.example'],
        );

        $response = $middleware->process(
            request: $request,
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'ok'),
            ),
        );

        self::assertSame('ok', $response->body);
        self::assertArrayNotHasKey('Access-Control-Allow-Origin', $response->headers);
    }

    public function testReturnsPreflightResponseForAllowedOrigin(): void
    {
        $middleware = new CorsMiddleware(
            allowedOrigins: ['https://example.com'],
            allowedMethods: ['GET', 'POST', 'OPTIONS'],
            allowedHeaders: ['Content-Type', 'Authorization'],
            allowCredentials: true,
            maxAge: 300,
        );
        $request = new Request(
            method: 'OPTIONS',
            path: '/',
            query: [],
            body: [],
            server: [
                'HTTP_ORIGIN' => 'https://example.com',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            ],
        );

        $response = $middleware->process(
            request: $request,
            handler: new CallableRequestHandler(
                handler: static fn (Request $request): Response => new Response(body: 'should not run'),
            ),
        );

        self::assertSame(204, $response->status);
        self::assertSame('', $response->body);
        self::assertSame('GET, POST, OPTIONS', $response->headers['Access-Control-Allow-Methods'] ?? null);
        self::assertSame('Content-Type, Authorization', $response->headers['Access-Control-Allow-Headers'] ?? null);
        self::assertSame('true', $response->headers['Access-Control-Allow-Credentials'] ?? null);
        self::assertSame('300', $response->headers['Access-Control-Max-Age'] ?? null);
    }
}
