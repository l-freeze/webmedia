<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Tag\Store;

use App\Cms\Application\Tag\Save\Input;
use App\Cms\Application\Tag\Save\Interactor;
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
            $this->session->flashValue(key: 'tag.form_values', value: $request->body);
            $this->session->flash(key: 'tag.form_error', value: '不正な送信です。');

            return Response::redirect(location: '/admin/tags');
        }

        $output = $this->save->execute(input: new Input(data: $request->body));
        if (!$output->saved) {
            $this->session->flashValue(key: 'tag.form_values', value: $request->body);
            $this->session->flash(key: 'tag.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/tags');
        }

        $this->session->flash(key: 'success', value: '保存しました');
        return Response::redirect(location: '/admin/tags');
    }
}
