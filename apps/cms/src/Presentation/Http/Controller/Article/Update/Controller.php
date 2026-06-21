<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Update;

use App\Cms\Application\Article\Save\Input;
use App\Cms\Application\Article\Save\Interactor;
use App\Cms\Application\Auth\Authorization\AuthorizationException;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private CmsGuard $guard,
        private Interactor $save,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();
        if ($currentUser === null) {
            return Response::redirect(location: '/admin/login');
        }

        $id = (int) $vars['id'];
        try {
            $output = $this->save->execute(input: new Input(id: $id, data: $request->body, currentUser: $currentUser));
        } catch (AuthorizationException) {
            return new Response(body: 'Forbidden', status: 403);
        }

        if ($output->articleId === null) {
            $this->session->flashValue(key: 'article.form_input', value: $this->articleFormData(data: $request->body, id: $id));
            $this->session->flash(key: 'article.form_error', value: $output->validation->firstMessage() ?? '入力内容を確認してください。');

            return Response::redirect(location: "/admin/articles/{$id}");
        }

        $this->session->flash(key: 'success', value: '保存しました');
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
}
