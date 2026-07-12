<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Article\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\ITemplateRenderer;
use App\FrontBusiness\Application\Article\Show\Input;
use App\FrontBusiness\Application\Article\Show\Interactor;
use App\FrontBusiness\Presentation\Seo\CanonicalUrl;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $interactor,
        private CanonicalUrl $canonicalUrl,
    )
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create(slug: $vars['slug'] ?? ''));
        $article = $output->article;
        if ($article === null) {
            return new Response(body: $this->renderer->render(template: 'state/simple.latte', params: [
                'title' => '404',
                'heading' => 'ページが見つかりません',
                'message' => '指定された記事は見つかりませんでした。',
            ]), status: Response::HTTP_NOT_FOUND);
        }

        $article = $article->toArray();
        $slug = is_string($article['slug'] ?? null) ? $article['slug'] : '';

        return new Response(body: $this->renderer->render(template: 'article/show.latte', params: [
            'title' => $article['title'],
            'description' => $article['excerpt'],
            'canonicalUrl' => $this->canonicalUrl->article($slug),
            'article' => $article,
            'related' => array_map(fn ($item): array => $item->toArray(), $output->related),
        ]));
    }
}
