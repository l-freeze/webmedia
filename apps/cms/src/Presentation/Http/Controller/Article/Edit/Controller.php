<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Edit;

use App\Cms\Application\Article\Edit;
use App\Cms\Application\Article\History;
use App\Cms\Application\Article\PublicationDateTime;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private Edit\Interactor $edit,
        private History\Interactor $history,
        private PublicationDateTime $publicationDateTime,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $id = (int) $vars['id'];
        $formArticle = $this->session->pullFlashValue(key: 'article.form_input');
        if (!is_array($formArticle)) {
            $formArticle = null;
        }
        /** @var array<string, mixed>|null $formArticle */

        return $this->editForm(
            output: $this->edit->execute(Edit\Input::create(id: $id)),
            action: "/admin/articles/{$id}",
            formArticle: $formArticle,
            error: $this->session->pullFlash(key: 'article.form_error'),
        );
    }

    /** @param array<string, mixed>|null $formArticle */
    private function editForm(Edit\Output $output, string $action, ?array $formArticle = null, ?string $error = null): Response
    {
        $article = $formArticle ?? $this->articleForEditor($output->article?->toArray());
        $articleIdValue = $article['id'] ?? null;
        $articleId = is_scalar($articleIdValue) ? (int) $articleIdValue : null;
        $versions = $articleId === null ? [] : array_map(
            fn ($version): array => $version->toArray(),
            $this->history->execute(History\Input::create(articleId: $articleId))->versions,
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

    /**
     * @param array<string, mixed>|null $article
     * @return array<string, mixed>|null
     */
    private function articleForEditor(?array $article): ?array
    {
        if ($article === null) {
            return null;
        }

        $publishedAt = $article['published_at'] ?? '';
        $publishedEndsAt = $article['published_ends_at'] ?? '';
        $article['published_at'] = $this->publicationDateTime->toEditor(is_string($publishedAt) ? $publishedAt : '');
        $article['published_ends_at'] = $this->publicationDateTime->toEditor(is_string($publishedEndsAt) ? $publishedEndsAt : '');

        return $article;
    }
}
