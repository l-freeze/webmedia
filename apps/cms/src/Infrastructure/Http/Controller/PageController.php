<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller;

use App\Cms\Application\Page\Edit\Input as EditInput;
use App\Cms\Application\Page\Edit\Interactor as EditInteractor;
use App\Cms\Application\Page\Edit\Output as EditOutput;
use App\Cms\Application\Page\History\Input as HistoryInput;
use App\Cms\Application\Page\History\Interactor as HistoryInteractor;
use App\Cms\Application\Page\List\Input as ListInput;
use App\Cms\Application\Page\List\Interactor as ListInteractor;
use App\Cms\Application\Page\Save\Input as SaveInput;
use App\Cms\Application\Page\Save\Interactor as SaveInteractor;
use App\Cms\Infrastructure\Http\AuthGuard;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class PageController
{
    use AuthGuard;

    public function __construct(
        private Renderer $renderer,
        private Session $session,
        private ListInteractor $list,
        private EditInteractor $edit,
        private SaveInteractor $save,
        private HistoryInteractor $history,
    ) {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $output = $this->list->execute(new ListInput());

        return new Response($this->renderer->render('page/index.latte', [
            'title' => '固定ページ管理',
            'pages' => array_map(fn ($row): array => $row->toArray(), $output->rows),
            'flash' => $this->session->pullFlash('success'),
        ]));
    }

    /** @param array<string, string> $vars */
    public function create(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        return $this->editForm($this->edit->execute(new EditInput(null)), '/admin/pages');
    }

    /** @param array<string, string> $vars */
    public function edit(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $id = (int) $vars['id'];
        return $this->editForm($this->edit->execute(new EditInput($id)), "/admin/pages/{$id}");
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

    private function editForm(EditOutput $output, string $action): Response
    {
        $page = $output->page?->toArray();
        $versions = $page === null ? [] : array_map(
            fn ($version): array => $version->toArray(),
            $this->history->execute(new HistoryInput((int) $page['id']))->versions,
        );

        return new Response($this->renderer->render('page/edit.latte', [
            'title' => $page ? '固定ページ編集' : '固定ページ作成',
            'action' => $action,
            'page' => $page ?? ['status' => 'draft'],
            'versions' => $versions,
            'flash' => $this->session->pullFlash('success'),
            'statuses' => [
                'draft' => '下書き',
                'published' => '公開',
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
        $pageId = $this->save->execute(new SaveInput($id, $request->body))->pageId;
        $this->session->flash('success', '保存しました');
        return Response::redirect('/admin/pages/' . $pageId->value);
    }
}
