<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\History;

use App\Cms\Application\Article\History\Input;
use App\Cms\Application\Article\History\Interactor;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $history,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->history->execute(Input::create(articleId: (int) $vars['id']));
        $versions = [];
        foreach ($output->versions as $version) {
            $versions[] = $version->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'article/history.latte', params: [
            'title' => '更新履歴',
            'articleId' => $output->articleId,
            'versions' => $versions,
        ]));
    }
}
