<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Index;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(private ITemplateRenderer $renderer)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(
            body: $this->renderer->render(template: 'index.latte', params: [
                'title' => '{{ name }}',
                'message' => '{{ name }} is running.',
                'path' => '{{ basePath }}',
            ]),
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
        );
    }
}
