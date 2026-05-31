<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Article\Edit;

use App\Cms\Application\Article\Edit;
use App\Cms\Application\Article\History;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class Controller
{
    public function __construct(
        private Renderer $renderer,
        private Session $session,
        private Csrf $csrf,
        private Edit\Interactor $edit,
        private History\Interactor $history,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->session->has(key: 'cms.user_id')) {
            return Response::redirect(location: '/admin/login');
        }

        $id = (int) $vars['id'];
        $formArticle = $this->session->pullFlashValue(key: 'article.form_input');
        if (!is_array($formArticle)) {
            $formArticle = null;
        }
        /** @var array<string, mixed>|null $formArticle */

        return $this->editForm(
            output: $this->edit->execute(input: new Edit\Input(id: $id)),
            action: "/admin/articles/{$id}",
            formArticle: $formArticle,
            error: $this->session->pullFlash(key: 'article.form_error'),
        );
    }

    /** @param array<string, mixed>|null $formArticle */
    private function editForm(Edit\Output $output, string $action, ?array $formArticle = null, ?string $error = null): Response
    {
        $article = $formArticle ?? $output->article?->toArray();
        $articleIdValue = $article['id'] ?? null;
        $articleId = is_scalar($articleIdValue) ? (int) $articleIdValue : null;
        $versions = $articleId === null ? [] : array_map(
            fn ($version): array => $version->toArray(),
            $this->history->execute(input: new History\Input(articleId: $articleId))->versions,
        );

        return new Response(body: $this->renderer->render(template: 'article/edit.latte', params: [
            'title' => '記事編集',
            'action' => $action,
            'csrf' => $this->csrf->token(),
            'article' => $article ?? ['status' => 'draft'],
            'categories' => array_map(fn ($category): array => $category->toArray(), $output->categories),
            'media' => array_map(fn ($item): array => $item->toArray(), $output->media),
            'flash' => $this->session->pullFlash(key: 'success'),
            'error' => $error,
            'versions' => $versions,
            'statuses' => [
                'draft' => '下書き',
                'published' => '表示',
                'private' => '非表示',
            ],
        ]));
    }
}
