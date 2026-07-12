<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\State\NoArticles;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(private ITemplateRenderer $renderer)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'state/simple.latte', params: [
            'title' => '記事がありません',
            'heading' => '記事がありません',
            'message' => '公開中の記事がまだありません。',
        ]));
    }
}
