<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http\Middleware;

use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @param array<int, string> $allowedOrigins
     * @param array<int, string> $allowedMethods
     * @param array<int, string> $allowedHeaders
     */
    public function __construct(
        private array $allowedOrigins,
        private array $allowedMethods = ['GET', 'POST', 'OPTIONS'],
        private array $allowedHeaders = ['Content-Type', 'X-Requested-With'],
        private bool $allowCredentials = false,
        private int $maxAge = 600,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $origin = $this->header(request: $request, name: 'Origin');
        if ($origin === null || !$this->isAllowedOrigin(origin: $origin)) {
            return $handler->handle(request: $request);
        }

        $response = $this->isPreflight(request: $request)
            ? new Response(body: '', status: 204, headers: ['Content-Type' => 'text/plain; charset=utf-8'])
            : $handler->handle(request: $request);

        $response = $response
            ->withHeader(name: 'Access-Control-Allow-Origin', value: $origin)
            ->withHeader(name: 'Vary', value: 'Origin')
            ->withHeader(name: 'Access-Control-Allow-Methods', value: implode(', ', $this->allowedMethods))
            ->withHeader(name: 'Access-Control-Allow-Headers', value: implode(', ', $this->allowedHeaders));

        if ($this->allowCredentials) {
            $response = $response->withHeader(name: 'Access-Control-Allow-Credentials', value: 'true');
        }

        if ($this->maxAge > 0 && $this->isPreflight(request: $request)) {
            $response = $response->withHeader(name: 'Access-Control-Max-Age', value: (string) $this->maxAge);
        }

        return $response;
    }

    private function isAllowedOrigin(string $origin): bool
    {
        return in_array('*', $this->allowedOrigins, true)
            || in_array($origin, $this->allowedOrigins, true);
    }

    private function isPreflight(Request $request): bool
    {
        return $request->method === 'OPTIONS'
            && $this->header(request: $request, name: 'Access-Control-Request-Method') !== null;
    }

    private function header(Request $request, string $name): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        $value = $request->server[$serverKey] ?? null;
        if ($value === null || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
