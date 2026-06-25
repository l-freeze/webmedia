<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Store;

use App\Cms\Application\Article\Save\Input;
use App\Cms\Application\Article\Save\Interactor;
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
        private Interactor $save,
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
            $output = $this->save->execute(input: new Input(id: null, data: $request->body, currentUser: $currentUser));
        } catch (AuthorizationException) {
            return new Response(body: 'Forbidden', status: 403);
        }

        if ($output->articleId === null) {
            $this->session->flashValue(key: 'article.form_input', value: $this->articleFormData(data: $request->body, id: null));
            $this->session->flash(key: 'article.form_error', value: $output->validation->firstMessage() ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/articles/create');
        }

        $this->session->flash(key: 'success', value: '保存しました');
        $this->events->dispatch(event: new AuditEvent(
            action: 'article.created',
            resourceType: 'article',
            result: 'success',
            actorUserId: (string) $currentUser->id,
            actorRole: $currentUser->role,
            resourceId: (string) $output->articleId->value,
            ipAddress: $this->serverString(request: $request, key: 'REMOTE_ADDR'),
            userAgent: $this->serverString(request: $request, key: 'HTTP_USER_AGENT'),
            requestId: $this->serverString(request: $request, key: 'HTTP_X_REQUEST_ID'),
            metadata: [
                'title' => $request->bodyInput(key: 'title'),
                'slug' => $request->bodyInput(key: 'slug'),
                'status' => $request->bodyInput(key: 'status', default: 'draft'),
            ],
        ));

        return Response::redirect(location: '/admin/articles/' . $output->articleId->value);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function articleFormData(array $data, ?int $id): array
    {
        $status = $data['status'] ?? 'draft';
        $data['status'] = is_scalar($status) ? (string) $status : 'draft';
        if ($id !== null) {
            $data['id'] = $id;
        }

        return $data;
    }

    private function serverString(Request $request, string $key): ?string
    {
        $value = $request->server[$key] ?? null;

        return is_scalar($value) ? (string) $value : null;
    }
}
