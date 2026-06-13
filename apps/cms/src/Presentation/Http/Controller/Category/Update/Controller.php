<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Category\Update;

use App\Cms\Application\Category\Update\Input;
use App\Cms\Application\Category\Update\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $update,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->update->execute(input: new Input(id: (int) $vars['id'], data: $request->body));
        if (!$output->updated) {
            $this->session->flash(key: 'category.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/categories');
        }

        $this->session->flash(key: 'success', value: 'カテゴリを更新しました');
        return Response::redirect(location: '/admin/categories');
    }
}
