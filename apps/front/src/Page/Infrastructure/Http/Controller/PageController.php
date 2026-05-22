<?php

declare(strict_types=1);

namespace App\Front\Page\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Page\Domain\PageQueryService;

final readonly class PageController
{
    public function __construct(private Renderer $renderer, private PageQueryService $pages)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $page = $this->pages->findPublishedBySlug($vars['slug'] ?? '');
        if ($page === null) {
            return new Response('Not Found', 404);
        }

        return new Response($this->renderer->render('page/show.latte', [
            'title' => $page['title'],
            'siteName' => 'WEB Media',
            'page' => $page,
        ]));
    }
}
