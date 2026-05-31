<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller\Tag\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Application\Tag\Show\Input;
use App\Front\Application\Tag\Show\Interactor;

final readonly class Controller
{
    public function __construct(
        private Renderer $renderer,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(input: new Input(slug: $vars['slug'] ?? ''));
        $tag = $output->tag?->toArray();

        return new Response(body: $this->renderer->render(template: 'list.latte', params: [
            'title' => $tag['name'] ?? 'タグ',
            'heading' => '#' . ($tag['name'] ?? 'タグ'),
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
        ]));
    }
}
