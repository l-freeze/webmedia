<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\AdminUser\Create;

use App\Cms\Application\AdminUser\Create\Input;
use App\Cms\Application\AdminUser\Create\Interactor;
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
        private Interactor $create,
        private CmsGuard $guard,
        private EventBusInterface $events,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->create->execute(input: new Input(data: $request->body));
        if (!$output->created) {
            $this->session->flashValue(key: 'admin_user.form_values', value: $request->body);
            $this->session->flash(key: 'admin_user.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/users');
        }

        $this->recordAudit(request: $request, userId: $output->userId, action: 'admin_user.created');
        $this->session->flash(key: 'success', value: '管理者アカウントを作成しました');

        return Response::redirect(location: '/admin/users');
    }

    private function recordAudit(Request $request, ?int $userId, string $action): void
    {
        $currentUser = $this->guard->currentUser();
        if ($currentUser === null) {
            return;
        }

        $this->events->dispatch(event: new AuditEvent(
            action: $action,
            resourceType: 'admin_user',
            result: 'success',
            actorUserId: (string) $currentUser->id,
            actorRole: $currentUser->role,
            resourceId: $userId !== null ? (string) $userId : null,
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
            requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
            after: [
                'email' => $this->bodyString(request: $request, key: 'email'),
                'name' => $this->bodyString(request: $request, key: 'name'),
                'role' => $this->bodyString(request: $request, key: 'role'),
            ],
        ));
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
