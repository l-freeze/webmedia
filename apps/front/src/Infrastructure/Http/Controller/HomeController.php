<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Application\Home\Show\Input;
use App\Front\Application\Home\Show\Interactor;

final readonly class HomeController
{
    public function __construct(private Renderer $renderer, private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(new Input());

        return new Response($this->renderer->render('index.latte', [
            'title' => 'トップ',
            'siteName' => 'WEB Media',
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
            'categories' => array_map(fn ($category): array => $category->toArray(), $output->categories),
        ]));
    }
}
