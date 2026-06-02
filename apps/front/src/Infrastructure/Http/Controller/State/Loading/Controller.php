<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller\State\Loading;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(private TemplateRenderer $renderer)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'state/simple.latte', params: [
            'title' => '読み込み中',
            'heading' => '読み込み中',
            'message' => '記事を読み込んでいます。',
        ]));
    }
}
