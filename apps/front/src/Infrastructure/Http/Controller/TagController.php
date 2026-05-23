<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Application\Tag\List\Input as ListInput;
use App\Front\Application\Tag\List\Interactor as ListInteractor;
use App\Front\Application\Tag\Show\Input as ShowInput;
use App\Front\Application\Tag\Show\Interactor as ShowInteractor;

final readonly class TagController
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
            'title' => 'タグ一覧',
            'siteName' => 'WEB Media',
            'heading' => 'タグ一覧',
            'basePath' => '/tags',
            'items' => array_map(fn ($item): array => $item->toArray(), $output->items),
        ]));
    }

    /** @param array<string, string> $vars */
    public function show(Request $request, array $vars = []): Response
    {
        $output = $this->show->execute(new ShowInput($vars['slug'] ?? ''));
        $tag = $output->tag?->toArray();

        return new Response($this->renderer->render('list.latte', [
            'title' => $tag['name'] ?? 'タグ',
            'siteName' => 'WEB Media',
            'heading' => '#' . ($tag['name'] ?? 'タグ'),
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
        ]));
    }
}
