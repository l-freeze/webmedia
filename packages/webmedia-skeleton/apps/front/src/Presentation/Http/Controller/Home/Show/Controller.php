<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Home\Show;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;
use App\Front\Application\Home\Show\Input;
use App\Front\Application\Home\Show\Interactor;
use App\Front\Presentation\Presenter\ArticleDatePresenter;

final readonly class Controller
{
    public function __construct(private ITemplateRenderer $renderer, private Interactor $interactor, private ArticleDatePresenter $articleDates)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create(page: (int) $request->input(key: 'page', default: '1')));
        $articles = [];
        foreach ($output->articles as $article) {
            $articles[] = $this->articleDates->present($article->toArray());
        }
        $categories = [];
        foreach ($output->categories as $category) {
            $categories[] = $category->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'index.latte', params: [
            'title' => 'トップ',
            'articles' => $articles,
            'categories' => $categories,
            'pagination' => $output->pagination->toArray(),
        ]));
    }
}
