<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\History;

use App\Cms\Application\Article\History\Input;
use App\Cms\Application\Article\History\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Interactor $history,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->history->execute(new Input(articleId: (int) $vars['id']));

        return new Response(body: $this->renderer->render(template: 'article/history.latte', params: [
            'title' => '更新履歴',
            'articleId' => $output->articleId,
            'versions' => array_map(fn ($version): array => $version->toArray(), $output->versions),
        ]));
    }
}
