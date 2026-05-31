<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Auth\Logout;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Csrf $csrf,
        private Session $session,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate(token: $request->input(key: '_csrf'))) {
            return new Response(body: 'Invalid CSRF token', status: 422);
        }

        $this->session->destroy();

        return Response::redirect(location: '/admin/login');
    }
}
