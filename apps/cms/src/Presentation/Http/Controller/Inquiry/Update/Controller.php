<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Inquiry\Update;

use App\Cms\Application\Inquiry\Update\Input;
use App\Cms\Application\Inquiry\Update\Interactor;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Event\Application\EventBusInterface;

final readonly class Controller
{
    public function __construct(
        private CmsGuard $guard,
        private Interactor $update,
        private Session $session,
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

        $id = (int) ($vars['id'] ?? 0);
        $status = $this->bodyString(request: $request, key: 'status');
        $adminNote = $this->bodyString(request: $request, key: 'admin_note');
        $output = $this->update->execute(input: new Input(id: $id, status: $status, adminNote: $adminNote));
        if (!$output->updated) {
            $message = $output->validation->errors[0]->message ?? '問い合わせの状態を更新できませんでした。';
            $this->session->flash(key: 'inquiry.form_error', value: $message);
            $this->session->flashValue(key: 'inquiry.form_input', value: [
                'status' => $status,
                'admin_note' => $adminNote,
            ]);

            return Response::redirect(location: "/admin/inquiries/{$id}");
        }

        $this->events->dispatch(event: new AuditEvent(
            action: 'inquiry.updated',
            resourceType: 'inquiry',
            result: 'success',
            actorUserId: (string) $currentUser->id,
            actorRole: $currentUser->role,
            resourceId: (string) $id,
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
            requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
            metadata: [
                'status' => $status,
                'admin_note_present' => $adminNote !== '',
            ],
        ));

        $this->session->flash(key: 'success', value: '問い合わせの状態を更新しました。');

        return Response::redirect(location: "/admin/inquiries/{$id}");
    }

    private function bodyString(Request $request, string $key): string
    {
        return $request->bodyInput(key: $key, default: '');
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
