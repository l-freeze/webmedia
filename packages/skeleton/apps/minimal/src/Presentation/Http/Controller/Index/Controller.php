<?php

declare(strict_types=1);

namespace App\Minimal\Presentation\Http\Controller\Index;

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
                'title' => 'minimal',
                'message' => 'minimal is running.',
                'path' => '/',
            ]),
            headers: ['Content-Type' => 'text/html; charset=utf-8'],
        );
    }
}
