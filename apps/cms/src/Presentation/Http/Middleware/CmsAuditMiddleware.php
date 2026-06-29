<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Middleware;

use App\Cms\Presentation\Audit\CmsAuditSummary;
use App\Cms\Presentation\Audit\CmsAuditTrail;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
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
        private CmsGuard $guard,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$this->shouldAudit(request: $request)) {
            return $handler->handle(request: $request);
        }

        $trail = new CmsAuditTrail();
        $auditedRequest = $request->withAttribute(key: CmsAuditTrail::ATTRIBUTE, value: $trail);

        try {
            $response = $handler->handle(request: $auditedRequest);
        } catch (Throwable $exception) {
            $this->record(
                request: $auditedRequest,
                result: 'failure',
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR,
            );

            throw $exception;
        }

        $summary = $response->status >= Response::HTTP_BAD_REQUEST
            ? null
            : $trail->summary();

        $this->record(
            request: $auditedRequest,
            result: $response->status >= Response::HTTP_BAD_REQUEST ? 'failure' : 'success',
            statusCode: $response->status,
            summary: $summary,
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

    private function record(
        Request $request,
        string $result,
        int $statusCode,
        ?CmsAuditSummary $summary = null,
    ): void
    {
        $metadata = [
            'method' => $request->method,
            'path' => $request->path,
            'status_code' => $statusCode,
        ];
        if ($summary !== null) {
            $metadata += $summary->metadata;
        }

        $action = $summary !== null ? $summary->action : 'cms.http_state_changed';
        $resourceType = $summary !== null ? $summary->resourceType : 'cms_request';
        $resourceId = $summary !== null ? $summary->resourceId : null;

        $this->events->dispatch(event: new AuditEvent(
            action: $action,
            resourceType: $resourceType,
            result: $result,
            actorUserId: $this->actorUserId(),
            actorRole: $this->actorRole(),
            resourceId: $resourceId,
            ipAddress: $request->server['REMOTE_ADDR'] ?? null,
            userAgent: $request->server['HTTP_USER_AGENT'] ?? null,
            requestId: $request->server['HTTP_X_REQUEST_ID'] ?? null,
            metadata: $metadata,
        ));
    }

    private function actorUserId(): ?string
    {
        $user = $this->guard->currentUser();

        return $user === null ? null : (string) $user->id;
    }

    private function actorRole(): ?string
    {
        $user = $this->guard->currentUser();

        return $user === null ? null : $user->role;
    }
}
