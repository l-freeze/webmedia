<?php

declare(strict_types=1);

namespace App\Front\Article\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Article\Domain\ArticleQueryService;

final readonly class SearchController
{
    public function __construct(private Renderer $renderer, private ArticleQueryService $articles)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $query = $request->input('q');
        $articles = $query === '' ? [] : $this->articles->search($query);

        return new Response($this->renderer->render('list.latte', [
            'title' => '検索結果',
            'siteName' => 'WEB Media',
            'heading' => $query === '' ? '検索' : "「{$query}」の検索結果",
            'lead' => count($articles) . '件の記事が見つかりました。',
            'articles' => $articles,
            'query' => $query,
        ]));
    }
}
