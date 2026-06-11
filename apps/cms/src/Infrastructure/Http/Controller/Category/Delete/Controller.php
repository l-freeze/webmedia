<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Category\Delete;

use App\Cms\Application\Category\Delete\Input;
use App\Cms\Application\Category\Delete\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Csrf $csrf,
        private Interactor $delete,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate(token: $request->input(key: '_csrf'))) {
            return new Response(body: 'Invalid CSRF token', status: 422);
        }

        $output = $this->delete->execute(input: new Input(id: (int) $vars['id']));
        $this->session->flash(key: 'success', value: $output->deleted ? 'カテゴリを削除しました' : ($output->error ?? '削除できませんでした'));

        return Response::redirect(location: '/admin/categories');
    }
}
