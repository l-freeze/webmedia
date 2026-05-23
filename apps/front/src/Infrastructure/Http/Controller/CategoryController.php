<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Application\Category\List\Input as ListInput;
use App\Front\Application\Category\List\Interactor as ListInteractor;
use App\Front\Application\Category\Show\Input as ShowInput;
use App\Front\Application\Category\Show\Interactor as ShowInteractor;

final readonly class CategoryController
{
    public function __construct(
        private Renderer $renderer,
        private ListInteractor $list,
        private ShowInteractor $show,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(new ListInput());

        return new Response($this->renderer->render('taxonomy/index.latte', [
            'title' => 'カテゴリ一覧',
            'siteName' => 'WEB Media',
            'heading' => 'カテゴリ一覧',
            'basePath' => '/categories',
            'items' => array_map(fn ($item): array => $item->toArray(), $output->items),
        ]));
    }

    /** @param array<string, string> $vars */
    public function show(Request $request, array $vars = []): Response
    {
        $output = $this->show->execute(new ShowInput($vars['slug'] ?? ''));
        $category = $output->category?->toArray();

        return new Response($this->renderer->render('list.latte', [
            'title' => $category['name'] ?? 'カテゴリ',
            'siteName' => 'WEB Media',
            'heading' => $category['name'] ?? 'カテゴリ',
            'lead' => $category['description'] ?? '',
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
        ]));
    }
}
