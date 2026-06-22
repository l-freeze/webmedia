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

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private CmsGuard $guard,
        private Interactor $delete,
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
            return new Response(body: 'Forbidden', status: 403);
        }

        $this->session->flash(key: 'success', value: $output->deleted ? '記事をゴミ箱へ移動しました' : ($output->error ?? '記事を削除できませんでした'));

        return Response::redirect(location: '/admin/articles');
    }
}
