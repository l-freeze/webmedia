<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Store;

use App\Cms\Application\Article\Save\Input;
use App\Cms\Application\Article\Save\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Csrf $csrf,
        private Interactor $save,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate(token: $request->input(key: '_csrf'))) {
            return new Response(body: 'Invalid CSRF token', status: 422);
        }

        $output = $this->save->execute(input: new Input(id: null, data: $request->body));
        if ($output->articleId === null) {
            $this->session->flashValue(key: 'article.form_input', value: $this->articleFormData(data: $request->body, id: null));
            $this->session->flash(key: 'article.form_error', value: $output->validation->firstMessage() ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/articles/create');
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
