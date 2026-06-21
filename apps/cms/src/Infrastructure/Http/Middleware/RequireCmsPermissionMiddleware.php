<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Middleware;

use App\Cms\Application\Auth\Authorization\AuthorizationService;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Middleware\MiddlewareInterface;
use App\Framework\Http\Middleware\RequestHandlerInterface;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Routing\Router;

final readonly class RequireCmsPermissionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CmsGuard $guard,
        private AuthorizationService $authorization,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $permission = $this->requiredPermission(request: $request);
        if ($permission === null) {
            return $handler->handle(request: $request);
        }

        $user = $this->guard->currentUser();
        if ($user === null) {
            return Response::redirect(location: '/admin/login');
        }

        if (!$this->authorization->can(user: $user, permission: $permission)) {
            return new Response(body: 'Forbidden', status: 403);
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
}
