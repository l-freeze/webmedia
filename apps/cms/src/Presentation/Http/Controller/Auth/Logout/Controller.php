<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Auth\Logout;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Shared\Event\Application\EventBusInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private EventBusInterface $events,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $userId = $this->session->get(key: 'cms.user_id');
        $role = $this->session->get(key: 'cms.role');

        $this->events->dispatch(event: new SecurityLogEvent(
            eventType: 'cms.logout',
            result: 'success',
            actorUserId: is_scalar($userId) ? (string) $userId : null,
            actorRole: is_scalar($role) ? (string) $role : null,
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
            requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
        ));

        $this->session->destroy();

        return Response::redirect('/admin/login');
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
