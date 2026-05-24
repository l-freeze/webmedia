<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller;

use App\Cms\Application\Article\Edit\Input as EditInput;
use App\Cms\Application\Article\Edit\Interactor as EditInteractor;
use App\Cms\Application\Article\Edit\Output as EditOutput;
use App\Cms\Application\Article\History\Input as HistoryInput;
use App\Cms\Application\Article\History\Interactor as HistoryInteractor;
use App\Cms\Application\Article\List\Input as ListInput;
use App\Cms\Application\Article\List\Interactor as ListInteractor;
use App\Cms\Application\Article\Preview\Input as PreviewInput;
use App\Cms\Application\Article\Preview\Interactor as PreviewInteractor;
use App\Cms\Application\Article\Restore\Input as RestoreInput;
use App\Cms\Application\Article\Restore\Interactor as RestoreInteractor;
use App\Cms\Application\Article\Save\Input as SaveInput;
use App\Cms\Application\Article\Save\Interactor as SaveInteractor;
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
        private Markdown $markdown,
        private ListInteractor $list,
        private EditInteractor $edit,
        private SaveInteractor $save,
        private PreviewInteractor $preview,
        private HistoryInteractor $history,
        private RestoreInteractor $restore,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $output = $this->list->execute(new ListInput($request->input('q')));
        return new Response($this->renderer->render('article/index.latte', [
            'title' => '記事管理',
            'query' => $output->keyword,
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
            'flash' => $this->session->pullFlash('success'),
        ]));
    }

    /** @param array<string, string> $vars */
    public function create(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        return $this->editForm($this->edit->execute(new EditInput(null)), '/admin/articles');
    }

    /** @param array<string, string> $vars */
    public function edit(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $id = (int) $vars['id'];
        return $this->editForm($this->edit->execute(new EditInput($id)), "/admin/articles/{$id}");
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
        $article = $this->preview->execute(new PreviewInput((int) $vars['id']))->article;
        if ($article === null) {
            return new Response('Not Found', 404);
        }
        return new Response($this->renderer->render('article/preview.latte', [
            'title' => 'プレビュー',
            'article' => $article->toArray($this->markdown->toSafeHtml($article->bodyMarkdown)),
        ]));
    }

    /** @param array<string, string> $vars */
    public function history(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $output = $this->history->execute(new HistoryInput((int) $vars['id']));
        return new Response($this->renderer->render('article/history.latte', [
            'title' => '更新履歴',
            'articleId' => $output->articleId,
            'versions' => array_map(fn ($version): array => $version->toArray(), $output->versions),
        ]));
    }

    /** @param array<string, string> $vars */
    public function restore(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $this->restore->execute(new RestoreInput((int) $vars['id'], (int) $vars['version']));
        return Response::redirect('/admin/articles/' . $vars['id']);
    }

    private function editForm(EditOutput $output, string $action): Response
    {
        $article = $output->article?->toArray();
        return new Response($this->renderer->render('article/edit.latte', [
            'title' => $article ? '記事編集' : '記事作成',
            'action' => $action,
            'csrf' => $this->csrf->token(),
            'article' => $article ?? ['status' => 'draft'],
            'categories' => array_map(fn ($category): array => $category->toArray(), $output->categories),
            'flash' => $this->session->pullFlash('success'),
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
        $articleId = $this->save->execute(new SaveInput($id, $request->body))->articleId;
        $this->session->flash('success', '保存しました');
        return Response::redirect('/admin/articles/' . $articleId->value);
    }
}
