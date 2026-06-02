<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller\Category\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;
use App\Front\Application\Category\Show\Input;
use App\Front\Application\Category\Show\Interactor;

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
        $output = $this->interactor->execute(input: new Input(slug: $vars['slug'] ?? ''));
        $category = $output->category?->toArray();

        return new Response(body: $this->renderer->render(template: 'list.latte', params: [
            'title' => $category['name'] ?? 'カテゴリ',
            'heading' => $category['name'] ?? 'カテゴリ',
            'lead' => $category['description'] ?? '',
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
        ]));
    }
}
