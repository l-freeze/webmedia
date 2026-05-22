<?php

declare(strict_types=1);

namespace App\Cms\Article\Infrastructure\Http\Controller;

use App\Cms\Article\Domain\ArticleRepository;
use App\Cms\Article\Infrastructure\Persistence\PdoArticleRepository;
use App\Cms\Infrastructure\Http\AuthGuard;
use App\Framework\Content\Markdown;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class ArticleController
{
    use AuthGuard;

    public function __construct(
        private Renderer $renderer,
        private Session $session,
        private Csrf $csrf,
        private ArticleRepository $articles,
        private Markdown $markdown,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $query = $request->input('q');
        return new Response($this->renderer->render('article/index.latte', [
            'title' => '記事管理',
            'query' => $query,
            'articles' => $this->articles->list($query),
        ]));
    }

    /** @param array<string, string> $vars */
    public function create(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        return $this->editForm(null, '/admin/articles');
    }

    /** @param array<string, string> $vars */
    public function edit(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $id = (int) $vars['id'];
        return $this->editForm($this->articles->find($id), "/admin/articles/{$id}");
    }

    /** @param array<string, string> $vars */
    public function store(Request $request, array $vars = []): Response
    {
        return $this->save($request, null);
    }

    /** @param array<string, string> $vars */
    public function update(Request $request, array $vars = []): Response
    {
        return $this->save($request, (int) $vars['id']);
    }

    /** @param array<string, string> $vars */
    public function preview(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $article = $this->articles->find((int) $vars['id']);
        if ($article === null) {
            return new Response('Not Found', 404);
        }
        $article['body_html'] = $this->markdown->toSafeHtml((string) $article['body_markdown']);
        $article['status_label'] = PdoArticleRepository::statusLabel((string) $article['status']);
        return new Response($this->renderer->render('article/preview.latte', [
            'title' => 'プレビュー',
            'article' => $article,
        ]));
    }

    /** @param array<string, string> $vars */
    public function history(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $id = (int) $vars['id'];
        return new Response($this->renderer->render('article/history.latte', [
            'title' => '更新履歴',
            'articleId' => $id,
            'versions' => $this->articles->versions($id),
        ]));
    }

    /** @param array<string, string> $vars */
    public function restore(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $this->articles->restore((int) $vars['id'], (int) $vars['version']);
        return Response::redirect('/admin/articles/' . $vars['id']);
    }

    /** @param array<string, mixed>|null $article */
    private function editForm(?array $article, string $action): Response
    {
        return new Response($this->renderer->render('article/edit.latte', [
            'title' => $article ? '記事編集' : '記事作成',
            'action' => $action,
            'csrf' => $this->csrf->token(),
            'article' => $article ?? ['status' => 'draft'],
            'categories' => $this->articles->categories(),
            'statuses' => [
                'draft' => '下書き',
                'published' => '公開中',
                'scheduled' => '公開予約',
                'private' => '非公開',
                'trashed' => 'ゴミ箱',
            ],
        ]));
    }

    private function save(Request $request, ?int $id): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        if (!$this->csrf->validate($request->input('_csrf'))) {
            return new Response('Invalid CSRF token', 422);
        }
        $articleId = $this->articles->save($id, $request->body);
        return Response::redirect('/admin/articles/' . $articleId);
    }
}
