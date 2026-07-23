<?php

declare(strict_types=1);

namespace App\Shared\Security\Infrastructure\Http;

use Pera\Framework\Http\Middleware\IMiddleware;
use Pera\Framework\Http\Middleware\IRequestHandler;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;

final readonly class SecurityHeadersMiddleware implements IMiddleware
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private array $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' https://unpkg.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; img-src 'self' data: https:; font-src 'self' data:; object-src 'none'; base-uri 'self'; frame-ancestors 'none'; form-action 'self'",
        ],
    ) {
    }

    public function process(Request $request, IRequestHandler $handler): Response
    {
        $response = $handler->handle(request: $request);

        foreach ($this->headers as $name => $value) {
            if (!array_key_exists($name, $response->headers)) {
                $response = $response->withHeader(name: $name, value: $value);
            }
        }

        return $response;
    }
}
