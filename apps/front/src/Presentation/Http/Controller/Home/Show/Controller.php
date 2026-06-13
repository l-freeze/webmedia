<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Home\Show;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;
use App\Front\Application\Home\Show\Input;
use App\Front\Application\Home\Show\Interactor;

final readonly class Controller
{
    public function __construct(private TemplateRenderer $renderer, private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(input: new Input(page: (int) $request->input(key: 'page', default: '1')));

        return new Response(body: $this->renderer->render(template: 'index.latte', params: [
            'title' => 'トップ',
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
            'categories' => array_map(fn ($category): array => $category->toArray(), $output->categories),
            'pagination' => $output->pagination->toArray(),
        ]));
    }
}
