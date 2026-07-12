<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Home\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\ITemplateRenderer;
use App\FrontBusiness\Application\Home\Show\Input;
use App\FrontBusiness\Application\Home\Show\Interactor;

final readonly class Controller
{
    public function __construct(private ITemplateRenderer $renderer, private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create(page: (int) $request->input(key: 'page', default: '1')));

        return new Response(body: $this->renderer->render(template: 'index.latte', params: [
            'title' => 'トップ',
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
            'categories' => array_map(fn ($category): array => $category->toArray(), $output->categories),
            'pagination' => $output->pagination->toArray(),
        ]));
    }
}
