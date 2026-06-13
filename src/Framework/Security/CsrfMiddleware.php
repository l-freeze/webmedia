<?php

declare(strict_types=1);

namespace App\Framework\Security;

use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class CsrfMiddleware implements MiddlewareInterface
{
    /** @param list<string> $excludedPaths */
    public function __construct(
        private Csrf $csrf,
        private array $excludedPaths = [],
        private bool $enabled = true,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$this->enabled) {
            return $handler->handle(request: $request);
        }

        if (!$this->requiresCsrf(request: $request)) {
            return $handler->handle(request: $request);
        }

        if (!$this->csrf->validate(token: $request->bodyInput(key: '_csrf'))) {
            return new Response(body: 'Invalid CSRF token', status: 422);
        }

        return $handler->handle(request: $request);
    }

    private function requiresCsrf(Request $request): bool
    {
        if (!in_array($request->method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        foreach ($this->excludedPaths as $path) {
            if ($this->matchesPath(pattern: $path, path: $request->path)) {
                return false;
            }
        }

        return true;
    }

    private function matchesPath(string $pattern, string $path): bool
    {
        if ($pattern === $path) {
            return true;
        }

        if (str_ends_with($pattern, '*')) {
            return str_starts_with($path, rtrim($pattern, '*'));
        }

        return false;
    }
}
