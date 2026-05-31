<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller\Page\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Application\Page\Show\Input;
use App\Front\Application\Page\Show\Interactor;

final readonly class Controller
{
    public function __construct(private Renderer $renderer, private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $page = $this->interactor->execute(input: new Input(slug: $vars['slug'] ?? ''))->page;
        if ($page === null) {
            return new Response(body: 'Not Found', status: 404);
        }
        $page = $page->toArray();

        return new Response(body: $this->renderer->render(template: 'page/show.latte', params: [
            'title' => $page['title'],
            'page' => $page,
        ]));
    }
}
