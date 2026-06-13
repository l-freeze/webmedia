<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Auth\Logout;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $this->session->destroy();

        return Response::redirect(location: '/admin/login');
    }
}
