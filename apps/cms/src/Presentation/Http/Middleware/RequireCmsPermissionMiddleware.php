<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Middleware;

use App\Cms\Application\Auth\AuthorizeRoute\Input;
use App\Cms\Application\Auth\AuthorizeRoute\Interactor;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Router;
use App\Shared\Event\Application\EventBusInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;

final readonly class RequireCmsPermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CmsGuard $guard,
        private Interactor $authorizeRoute,
        private EventBusInterface $events,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $permission = $this->requiredPermission($request);
        if ($permission === null) {
            return $handler->handle(request: $request);
        }

        if (!$this->guard->isAuthenticated()) {
            $this->events->dispatch(event: $this->securityEvent(
                request: $request,
                eventType: 'cms.authorization.unauthenticated',
                result: 'failure',
                permission: $permission,
            ));

            return Response::redirect('/admin/login');
        }

        $user = $this->guard->currentUser();
        $authorization = $this->authorizeRoute->execute(Input::create(
            actorRole: $user->role,
            requiredPermission: $permission,
        ));
        if (!$authorization->allowed) {
            $this->events->dispatch(event: $this->securityEvent(
                request: $request,
                eventType: 'cms.authorization.denied',
                result: 'failure',
                permission: $permission,
                actorUserId: (string) $user->id,
                actorRole: $user->role,
            ));

            return new Response(body: 'Forbidden', status: Response::HTTP_FORBIDDEN);
        }

        return $handler->handle(request: $request);
    }

    private function requiredPermission(Request $request): ?string
    {
        $metadata = $request->attribute(key: Router::ROUTE_METADATA_ATTRIBUTE, default: []);
        if (!is_array($metadata)) {
            return null;
        }

        $permission = $metadata['requires_permission'] ?? null;

        return is_string($permission) && $permission !== '' ? $permission : null;
    }

    private function securityEvent(
        Request $request,
        string $eventType,
        string $result,
        string $permission,
        ?string $actorUserId = null,
        ?string $actorRole = null,
    ): SecurityLogEvent {
        return new SecurityLogEvent(
            eventType: $eventType,
            result: $result,
            actorUserId: $actorUserId,
            actorRole: $actorRole,
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
            requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
            metadata: [
                'method' => $request->method,
                'path' => $request->path,
                'permission' => $permission,
            ],
        );
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
