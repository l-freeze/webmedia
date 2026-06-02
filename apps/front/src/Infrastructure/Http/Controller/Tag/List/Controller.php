<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller\Tag\List;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;
use App\Front\Application\Tag\List\Input;
use App\Front\Application\Tag\List\Interactor;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(input: new Input());

        return new Response(body: $this->renderer->render(template: 'taxonomy/index.latte', params: [
            'title' => 'タグ一覧',
            'heading' => 'タグ一覧',
            'basePath' => '/tags',
            'items' => array_map(fn ($item): array => $item->toArray(), $output->items),
        ]));
    }
}
