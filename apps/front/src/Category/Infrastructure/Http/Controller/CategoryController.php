<?php

declare(strict_types=1);

namespace App\Front\Category\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Category\Domain\CategoryQueryService;

final readonly class CategoryController
{
    public function __construct(private Renderer $renderer, private CategoryQueryService $categories)
    {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        return new Response($this->renderer->render('taxonomy/index.latte', [
            'title' => 'カテゴリ一覧',
            'siteName' => 'WEB Media',
            'heading' => 'カテゴリ一覧',
            'basePath' => '/categories',
            'items' => $this->categories->all(),
        ]));
    }

    /** @param array<string, string> $vars */
    public function show(Request $request, array $vars = []): Response
    {
        $category = $this->categories->find($vars['slug'] ?? '');
        return new Response($this->renderer->render('list.latte', [
            'title' => $category['name'] ?? 'カテゴリ',
            'siteName' => 'WEB Media',
            'heading' => $category['name'] ?? 'カテゴリ',
            'lead' => $category['description'] ?? '',
            'articles' => $this->categories->articles($vars['slug'] ?? ''),
        ]));
    }
}
