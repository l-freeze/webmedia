<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Article\Show;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;
use App\Front\Application\Article\Show\Input;
use App\Front\Application\Article\Show\Interactor;
use App\Front\Presentation\Seo\CanonicalUrl;
use App\Front\Presentation\Presenter\ArticleDatePresenter;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $interactor,
        private CanonicalUrl $canonicalUrl,
        private ArticleDatePresenter $articleDates,
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

        $article = $this->articleDates->present($article->toArray());
        $slug = is_string($article['slug'] ?? null) ? $article['slug'] : '';
        $related = [];
        foreach ($output->related as $item) {
            $related[] = $this->articleDates->present($item->toArray());
        }

        return new Response(body: $this->renderer->render(template: 'article/show.latte', params: [
            'title' => $article['title'],
            'description' => $article['excerpt'],
            'canonicalUrl' => $this->canonicalUrl->article($slug),
            'article' => $article,
            'related' => $related,
        ]));
    }
}
