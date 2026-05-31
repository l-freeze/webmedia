<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Article\Restore;

use App\Cms\Application\Article\Restore\Input;
use App\Cms\Application\Article\Restore\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Csrf $csrf,
        private Interactor $restore,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->session->has(key: 'cms.user_id')) {
            return Response::redirect(location: '/admin/login');
        }
        if (!$this->csrf->validate(token: $request->input(key: '_csrf'))) {
            return new Response(body: 'Invalid CSRF token', status: 422);
        }

        $this->restore->execute(input: new Input(articleId: (int) $vars['id'], versionNo: (int) $vars['version']));

        return Response::redirect(location: '/admin/articles/' . $vars['id']);
    }
}
