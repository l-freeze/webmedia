<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Tag\Update;

use App\Cms\Application\Tag\Update\Input;
use App\Cms\Application\Tag\Update\Interactor;
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
            $this->session->flash(key: 'tag.form_error', value: '不正な送信です。');

            return Response::redirect(location: '/admin/tags');
        }

        $output = $this->update->execute(input: new Input(id: (int) $vars['id'], data: $request->body));
        if (!$output->updated) {
            $this->session->flash(key: 'tag.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/tags');
        }

        $this->session->flash(key: 'success', value: 'タグを更新しました');
        return Response::redirect(location: '/admin/tags');
    }
}
