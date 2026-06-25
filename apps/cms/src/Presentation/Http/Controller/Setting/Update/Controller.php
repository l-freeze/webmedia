<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Setting\Update;

use App\Cms\Application\Setting\Save\Input;
use App\Cms\Application\Setting\Save\Interactor;
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
        private Interactor $save,
        private CmsGuard $guard,
        private EventBusInterface $events,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $this->save->execute(input: new Input(data: $request->body));
        $currentUser = $this->guard->currentUser();
        if ($currentUser !== null) {
            $this->events->dispatch(event: new AuditEvent(
                action: 'settings.updated',
                resourceType: 'site_settings',
                result: 'success',
                actorUserId: (string) $currentUser->id,
                actorRole: $currentUser->role,
                ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
                userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
                requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
                metadata: [
                    'keys' => array_values(array_filter(array_keys($request->body), static fn (string $key): bool => $key !== '_csrf')),
                ],
            ));
        }

        $this->session->flash(key: 'success', value: '保存しました');

        return Response::redirect(location: '/admin/settings');
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
