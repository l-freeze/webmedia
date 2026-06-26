<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\AdminUser\Delete;

use App\Cms\Application\AdminUser\Delete\Input;
use App\Cms\Application\AdminUser\Delete\Interactor;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Event\Application\EventBusInterface;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $delete,
        private CmsGuard $guard,
        private EventBusInterface $events,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();
        if ($currentUser === null) {
            return Response::redirect(location: '/admin/login');
        }

        $userId = (int) $vars['id'];
        $output = $this->delete->execute(input: new Input(id: $userId, currentUser: $currentUser));
        if (!$output->deleted) {
            $this->session->flash(key: 'admin_user.form_error', value: $output->error ?? '削除できませんでした。');

            return Response::redirect(location: '/admin/users');
        }

        $this->events->dispatch(event: new AuditEvent(
            action: 'admin_user.deleted',
            resourceType: 'admin_user',
            result: 'success',
            actorUserId: (string) $currentUser->id,
            actorRole: $currentUser->role,
            resourceId: (string) $userId,
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
            requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
        ));

        $this->session->flash(key: 'success', value: '管理者アカウントを削除しました');
        return Response::redirect(location: '/admin/users');
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
