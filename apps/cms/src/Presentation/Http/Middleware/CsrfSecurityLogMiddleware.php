<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Middleware;

use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Shared\Event\Application\EventBusInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;

final readonly class CsrfSecurityLogMiddleware implements MiddlewareInterface
{
    /** @param list<string> $excludedPaths */
    public function __construct(
        private Csrf $csrf,
        private EventBusInterface $events,
        private array $excludedPaths = [],
        private bool $enabled = true,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$this->enabled) {
            return $handler->handle(request: $request);
        }

        if (!$this->requiresCsrf($request)) {
            return $handler->handle(request: $request);
        }

        if (!$this->csrf->validate(token: $request->bodyInput(key: '_csrf'))) {
            $this->events->dispatch(event: new SecurityLogEvent(
                eventType: 'cms.csrf.denied',
                result: 'failure',
                ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
                userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
                requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
                metadata: [
                    'method' => $request->method,
                    'path' => $request->path,
                ],
            ));

            return new Response(body: 'Invalid CSRF token', status: Response::HTTP_UNPROCESSABLE_ENTITY);
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

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
