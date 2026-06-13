<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Create;

use App\Cms\Application\Article\Edit\Input;
use App\Cms\Application\Article\Edit\Interactor;
use App\Cms\Application\Article\Edit\Output;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private Interactor $edit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $formArticle = $this->session->pullFlashValue(key: 'article.form_input');
        if (!is_array($formArticle)) {
            $formArticle = null;
        }
        /** @var array<string, mixed>|null $formArticle */

        return $this->editForm(
            output: $this->edit->execute(input: new Input(id: null)),
            action: '/admin/articles',
            formArticle: $formArticle,
            error: $this->session->pullFlash(key: 'article.form_error'),
        );
    }

    /** @param array<string, mixed>|null $formArticle */
    private function editForm(Output $output, string $action, ?array $formArticle = null, ?string $error = null): Response
    {
        $article = $formArticle ?? $output->article?->toArray();

        return new Response(body: $this->renderer->render(template: 'article/edit.latte', params: [
            'title' => '記事作成',
            'action' => $action,
            'csrf' => $this->csrf->token(),
            'article' => $article ?? ['status' => 'draft'],
            'categories' => array_map(fn ($category): array => $category->toArray(), $output->categories),
            'media' => array_map(fn ($item): array => $item->toArray(), $output->media),
            'flash' => $this->session->pullFlash(key: 'success'),
            'error' => $error,
            'versions' => [],
            'statuses' => [
                'draft' => '下書き',
                'published' => '表示',
                'private' => '非表示',
            ],
        ]));
    }
}
