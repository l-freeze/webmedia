<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Update;

use App\Cms\Application\Article\Save\Input;
use App\Cms\Application\Article\Save\Interactor;
use App\Cms\Application\Auth\Policy\Exception\AuthorizationException;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private CmsGuard $guard,
        private Interactor $save,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();
        if ($currentUser === null) {
            return Response::redirect('/admin/login');
        }

        $id = (int) $vars['id'];
        try {
            $output = $this->save->execute(new Input(id: $id, data: $request->body, currentUser: $currentUser));
        } catch (AuthorizationException) {
            return new Response(body: 'Forbidden', status: Response::HTTP_FORBIDDEN);
        }

        if ($output->articleId === null) {
            $this->session->flashValue(key: 'article.form_input', value: $this->articleFormData(data: $request->body, id: $id));
            $this->session->flash(key: 'article.form_error', value: $output->validation->firstMessage() ?? '入力内容を確認してください。');

            return Response::redirect("/admin/articles/{$id}");
        }

        $this->session->flash(key: 'success', value: '保存しました');
        $this->audit->articleSaved(request: $request, articleId: $output->articleId->value, created: false);

        return Response::redirect('/admin/articles/' . $output->articleId->value);
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
