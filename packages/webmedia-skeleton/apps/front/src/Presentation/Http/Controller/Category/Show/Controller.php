<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Category\Show;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;
use App\Front\Application\Category\Show\Input;
use App\Front\Application\Category\Show\Interactor;
use App\Front\Presentation\Seo\CanonicalUrl;
use App\Front\Presentation\Presenter\ArticleDatePresenter;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $interactor,
        private CanonicalUrl $canonicalUrl,
        private ArticleDatePresenter $articleDates,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create(slug: $vars['slug'] ?? ''));
        $category = $output->category?->toArray();
        $articles = [];
        foreach ($output->articles as $article) {
            $articles[] = $this->articleDates->present($article->toArray());
        }

        return new Response(body: $this->renderer->render(template: 'list.latte', params: [
            'title' => $category['name'] ?? 'カテゴリ',
            'heading' => $category['name'] ?? 'カテゴリ',
            'lead' => $category['description'] ?? '',
            'canonicalUrl' => $category !== null ? $this->canonicalUrl->category($category['slug']) : null,
            'articles' => $articles,
        ]));
    }
}
