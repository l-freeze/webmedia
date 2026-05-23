<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Application\Page\Show\Input;
use App\Front\Application\Page\Show\Interactor;

final readonly class PageController
{
    public function __construct(private Renderer $renderer, private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $page = $this->interactor->execute(new Input($vars['slug'] ?? ''))->page;
        if ($page === null) {
            return new Response('Not Found', 404);
        }
        $page = $page->toArray();

        return new Response($this->renderer->render('page/show.latte', [
            'title' => $page['title'],
            'siteName' => 'WEB Media',
            'page' => $page,
        ]));
    }
}
