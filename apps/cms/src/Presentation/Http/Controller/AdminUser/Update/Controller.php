<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\AdminUser\Update;

use App\Cms\Application\AdminUser\Update\Input;
use App\Cms\Application\AdminUser\Update\Interactor;
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
        private Interactor $update,
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
        $output = $this->update->execute(input: new Input(id: $userId, data: $request->body, currentUser: $currentUser));
        if (!$output->updated) {
            $this->session->flash(key: 'admin_user.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/users');
        }

        $this->events->dispatch(event: new AuditEvent(
            action: 'admin_user.updated',
            resourceType: 'admin_user',
            result: 'success',
            actorUserId: (string) $currentUser->id,
            actorRole: $currentUser->role,
            resourceId: (string) $userId,
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
            requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
            after: [
                'email' => $this->bodyString(request: $request, key: 'email'),
                'name' => $this->bodyString(request: $request, key: 'name'),
                'role' => $this->bodyString(request: $request, key: 'role'),
                'password_changed' => $this->bodyString(request: $request, key: 'password') !== '',
            ],
        ));

        $this->session->flash(key: 'success', value: '管理者アカウントを更新しました');
        return Response::redirect(location: '/admin/users');
    }

    private function bodyString(Request $request, string $key): string
    {
        $value = $request->body[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
