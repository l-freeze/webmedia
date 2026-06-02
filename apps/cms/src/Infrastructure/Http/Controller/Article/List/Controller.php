<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Article\List;

use App\Cms\Application\Article\List\Input;
use App\Cms\Application\Article\List\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Session $session,
        private Csrf $csrf,
        private Interactor $list,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(input: new Input(keyword: $request->input(key: 'q')));

        return new Response(body: $this->renderer->render(template: 'article/index.latte', params: [
            'title' => '記事管理',
            'query' => $output->keyword,
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
            'flash' => $this->session->pullFlash(key: 'success'),
            'csrf' => $this->csrf->token(),
        ]));
    }
}
