<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\List;

use App\Cms\Application\Article\List\Input;
use App\Cms\Application\Article\List\Interactor;
use App\Cms\Presentation\Presenter\Article\ArticleStatusPresenter;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\Security\Session;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private Interactor $list,
        private ArticleStatusPresenter $articleStatus,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(Input::create(keyword: $request->input(key: 'q')));
        $articles = [];
        foreach ($output->articles as $article) {
            $articles[] = [
                ...$article->toArray(),
                'status_label' => $this->articleStatus->label(
                    status: $article->status,
                    publishedAt: $article->publishedAt,
                    publishedEndsAt: $article->publishedEndsAt,
                ),
            ];
        }

        return new Response(body: $this->renderer->render(template: 'article/index.latte', params: [
            'title' => '記事管理',
            'query' => $output->keyword,
            'articles' => $articles,
            'flash' => $this->session->pullFlash(key: 'success'),
            'csrf' => $this->csrf->token(),
        ]));
    }
}
