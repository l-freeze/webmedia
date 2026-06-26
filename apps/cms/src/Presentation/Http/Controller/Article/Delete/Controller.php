<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Delete;

use App\Cms\Application\Article\Delete\Input;
use App\Cms\Application\Article\Delete\Interactor;
use App\Cms\Application\Auth\Authorization\Exception\AuthorizationException;
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
        private CmsGuard $guard,
        private Interactor $delete,
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

        try {
            $output = $this->delete->execute(input: new Input(id: (int) $vars['id'], currentUser: $currentUser));
        } catch (AuthorizationException) {
            return new Response(body: 'Forbidden', status: Response::HTTP_FORBIDDEN);
        }

        $this->session->flash(key: 'success', value: $output->deleted ? '記事をゴミ箱へ移動しました' : ($output->error ?? '記事を削除できませんでした'));
        if ($output->deleted) {
            $this->events->dispatch(event: new AuditEvent(
                action: 'article.trashed',
                resourceType: 'article',
                result: 'success',
                actorUserId: (string) $currentUser->id,
                actorRole: $currentUser->role,
                resourceId: (string) $vars['id'],
                ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
                userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
                requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
            ));
        }

        return Response::redirect(location: '/admin/articles');
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
