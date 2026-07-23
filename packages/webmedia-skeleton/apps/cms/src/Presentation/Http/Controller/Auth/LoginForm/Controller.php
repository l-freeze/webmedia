<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Auth\LoginForm;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'auth/login.latte', params: [
            'csrf' => $this->csrf->token(),
            'error' => null,
        ]));
    }
}
