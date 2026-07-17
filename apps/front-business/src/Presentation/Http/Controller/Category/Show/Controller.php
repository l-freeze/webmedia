<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Category\Show;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;
use App\FrontBusiness\Application\Category\Show\Input;
use App\FrontBusiness\Application\Category\Show\Interactor;
use App\FrontBusiness\Presentation\Seo\CanonicalUrl;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $interactor,
        private CanonicalUrl $canonicalUrl,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create(slug: $vars['slug'] ?? ''));
        $category = $output->category?->toArray();

        return new Response(body: $this->renderer->render(template: 'list.latte', params: [
            'title' => $category['name'] ?? 'カテゴリ',
            'heading' => $category['name'] ?? 'カテゴリ',
            'lead' => $category['description'] ?? '',
            'canonicalUrl' => $category !== null ? $this->canonicalUrl->category($category['slug']) : null,
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
        ]));
    }
}
