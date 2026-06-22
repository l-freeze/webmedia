<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Middleware;

use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Event\Application\EventBusInterface;
use Throwable;

final readonly class CmsAuditMiddleware implements MiddlewareInterface
{
    /** @var array<int, string> */
    private const DENIED_PATHS = [
        '/admin/login',
        '/admin/logout',
    ];

    /** @var array<int, string> */
    private const STATE_CHANGING_METHODS = [
        'POST',
        'PUT',
        'PATCH',
        'DELETE',
    ];

    public function __construct(
        private EventBusInterface $events,
        private Session $session,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$this->shouldAudit(request: $request)) {
            return $handler->handle(request: $request);
        }

        try {
            $response = $handler->handle(request: $request);
        } catch (Throwable $exception) {
            $this->record(request: $request, result: 'failure', statusCode: 500);

            throw $exception;
        }

        $this->record(
            request: $request,
            result: $response->status >= 400 ? 'failure' : 'success',
            statusCode: $response->status,
        );

        return $response;
    }

    private function shouldAudit(Request $request): bool
    {
        if (!$this->isAdminPath(path: $request->path)) {
            return false;
        }

        if (in_array($request->path, self::DENIED_PATHS, true)) {
            return false;
        }

        return in_array($request->method, self::STATE_CHANGING_METHODS, true);
    }

    private function isAdminPath(string $path): bool
    {
        return $path === '/admin' || str_starts_with($path, '/admin/');
    }

    private function record(Request $request, string $result, int $statusCode): void
    {
        $this->events->dispatch(event: new AuditEvent(
            action: 'cms.http_state_changed',
            resourceType: 'cms_request',
            result: $result,
            actorUserId: $this->actorUserId(),
            ipAddress: $request->server['REMOTE_ADDR'] ?? null,
            userAgent: $request->server['HTTP_USER_AGENT'] ?? null,
            requestId: $request->server['HTTP_X_REQUEST_ID'] ?? null,
            metadata: [
                'method' => $request->method,
                'path' => $request->path,
                'status_code' => $statusCode,
            ],
        ));
    }

    private function actorUserId(): ?string
    {
        $value = $this->session->get(key: 'cms.user_id');

        return is_scalar($value) ? (string) $value : null;
    }
}
