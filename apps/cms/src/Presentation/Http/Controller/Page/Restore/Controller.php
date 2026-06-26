<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Page\Restore;

use App\Cms\Application\Page\Restore\Input;
use App\Cms\Application\Page\Restore\Interactor;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Event\Application\EventBusInterface;

final readonly class Controller
{
    public function __construct(
        private CmsGuard $guard,
        private Interactor $restore,
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

        $this->restore->execute(input: new Input(pageId: (int) $vars['id'], versionNo: (int) $vars['version']));

        $this->events->dispatch(event: new AuditEvent(
            action: 'page.restored',
            resourceType: 'page',
            result: 'success',
            actorUserId: (string) $currentUser->id,
            actorRole: $currentUser->role,
            resourceId: (string) $vars['id'],
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
            requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
            metadata: [
                'version_no' => (int) $vars['version'],
            ],
        ));

        return Response::redirect(location: '/admin/pages/' . $vars['id']);
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
