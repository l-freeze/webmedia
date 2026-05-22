<?php

declare(strict_types=1);

namespace App\Front\Article\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Article\Domain\ArticleQueryService;
use App\Front\Category\Domain\CategoryQueryService;

final readonly class HomeController
{
    public function __construct(
        private Renderer $renderer,
        private ArticleQueryService $articles,
        private CategoryQueryService $categories,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response($this->renderer->render('index.latte', [
            'title' => 'トップ',
            'siteName' => 'WEB Media',
            'articles' => $this->articles->latest(),
            'categories' => $this->categories->all(),
        ]));
    }
}
