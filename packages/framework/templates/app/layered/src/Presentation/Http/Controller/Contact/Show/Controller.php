<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Contact\Show;

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
        return new Response(body: $this->renderer->render(template: 'contact.latte', params: [
            'title' => '{{ name }} contact',
            'action' => '{{ contactPath }}',
            'csrfToken' => $this->csrf->token(),
            'values' => ['name' => '', 'email' => '', 'message' => ''],
            'error' => null,
            'accepted' => false,
        ]));
    }
}
