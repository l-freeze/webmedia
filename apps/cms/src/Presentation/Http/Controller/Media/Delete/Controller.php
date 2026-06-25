<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Media\Delete;

use App\Cms\Application\Media\Delete\Input;
use App\Cms\Application\Media\Delete\Interactor;
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
        $output = $this->delete->execute(input: new Input(id: (int) ($vars['id'] ?? 0)));
        if (!$output->deleted) {
            $this->session->flash(key: 'error', value: $output->error ?? 'メディア削除に失敗しました。');
            return Response::redirect(location: '/admin/media');
        }

        $currentUser = $this->guard->currentUser();
        if ($currentUser !== null) {
            $this->events->dispatch(event: new AuditEvent(
                action: 'media.deleted',
                resourceType: 'media',
                result: 'success',
                actorUserId: (string) $currentUser->id,
                actorRole: $currentUser->role,
                resourceId: (string) ($vars['id'] ?? ''),
                ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
                userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
                requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
            ));
        }

        $this->session->flash(key: 'success', value: 'メディアを削除しました');
        return Response::redirect(location: '/admin/media');
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
