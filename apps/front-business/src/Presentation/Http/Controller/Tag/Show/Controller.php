<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Tag\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;
use App\FrontBusiness\Application\Tag\Show\Input;
use App\FrontBusiness\Application\Tag\Show\Interactor;
use App\FrontBusiness\Presentation\Seo\CanonicalUrl;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Interactor $interactor,
        private CanonicalUrl $canonicalUrl,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create(slug: $vars['slug'] ?? ''));
        $tag = $output->tag?->toArray();

        return new Response(body: $this->renderer->render(template: 'list.latte', params: [
            'title' => $tag['name'] ?? 'タグ',
            'heading' => '#' . ($tag['name'] ?? 'タグ'),
            'canonicalUrl' => $tag !== null ? $this->canonicalUrl->tag($tag['slug']) : null,
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
        ]));
    }
}
