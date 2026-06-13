<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Contact\Create;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'contact/form.latte', params: [
            'title' => '問い合わせ',
            'csrf' => $this->csrf->token(),
            'error' => null,
            'old' => [],
        ]));
    }
}
