<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Tag\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\ITemplateRenderer;
use App\Front\Application\Tag\Show\Input;
use App\Front\Application\Tag\Show\Interactor;
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
        $tag = $output->tag?->toArray();
        $articles = [];
        foreach ($output->articles as $article) {
            $articles[] = $this->articleDates->present($article->toArray());
        }

        return new Response(body: $this->renderer->render(template: 'list.latte', params: [
            'title' => $tag['name'] ?? 'タグ',
            'heading' => '#' . ($tag['name'] ?? 'タグ'),
            'canonicalUrl' => $tag !== null ? $this->canonicalUrl->tag($tag['slug']) : null,
            'articles' => $articles,
        ]));
    }
}
