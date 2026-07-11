<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Page\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;
use App\FrontBusiness\Application\Page\Show\Input;
use App\FrontBusiness\Application\Page\Show\Interactor;
use App\FrontBusiness\Presentation\Seo\CanonicalUrl;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Interactor $interactor,
        private CanonicalUrl $canonicalUrl,
    )
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $page = $this->interactor->execute(Input::create(slug: $vars['slug'] ?? ''))->page;
        if ($page === null) {
            return new Response(body: 'Not Found', status: Response::HTTP_NOT_FOUND);
        }
        $page = $page->toArray();
        $slug = is_string($page['slug'] ?? null) ? $page['slug'] : '';

        return new Response(body: $this->renderer->render(template: 'page/show.latte', params: [
            'title' => $page['title'],
            'canonicalUrl' => $this->canonicalUrl->page($slug),
            'page' => $page,
        ]));
    }
}
