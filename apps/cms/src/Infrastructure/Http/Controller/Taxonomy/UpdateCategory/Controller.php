<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Taxonomy\UpdateCategory;

use App\Cms\Application\Taxonomy\Update\Input;
use App\Cms\Application\Taxonomy\Update\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Csrf $csrf,
        private Interactor $update,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate(token: $request->input(key: '_csrf'))) {
            $this->session->flash(key: 'taxonomy.form_error', value: '不正な送信です。');

            return Response::redirect(location: '/admin/taxonomy');
        }

        $output = $this->update->execute(input: new Input(type: 'category', id: (int) $vars['id'], data: $request->body));
        if (!$output->updated) {
            $this->session->flash(key: 'taxonomy.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/taxonomy');
        }

        $this->session->flash(key: 'success', value: 'カテゴリを更新しました');
        return Response::redirect(location: '/admin/taxonomy');
    }

}
