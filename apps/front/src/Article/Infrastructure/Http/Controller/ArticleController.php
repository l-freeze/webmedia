<?php

declare(strict_types=1);

namespace App\Front\Article\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Article\Domain\ArticleQueryService;

final readonly class ArticleController
{
    public function __construct(private Renderer $renderer, private ArticleQueryService $articles)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $article = $this->articles->findPublishedBySlug($vars['slug'] ?? '');
        if ($article === null) {
            return new Response($this->renderer->render('state/simple.latte', [
                'title' => '404',
                'siteName' => 'WEB Media',
                'heading' => 'ページが見つかりません',
                'message' => '指定された記事は見つかりませんでした。',
            ]), 404);
        }

        return new Response($this->renderer->render('article/show.latte', [
            'title' => $article['title'],
            'siteName' => 'WEB Media',
            'description' => $article['excerpt'],
            'article' => $article,
            'related' => $this->articles->related((int) $article['id'], $article['category_id']),
        ]));
    }
}
