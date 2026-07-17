<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Page\Edit;

use App\Cms\Application\Page\Edit;
use App\Cms\Application\Page\History;
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
        private Edit\Interactor $edit,
        private History\Interactor $history,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $id = (int) $vars['id'];
        $formPage = $this->session->pullFlashValue(key: 'page.form_input');
        if (!is_array($formPage)) {
            $formPage = null;
        }
        /** @var array<string, mixed>|null $formPage */

        return $this->editForm(
            output: $this->edit->execute(Edit\Input::create(id: $id)),
            action: "/admin/pages/{$id}",
            formPage: $formPage,
            error: $this->session->pullFlash(key: 'page.form_error'),
        );
    }

    /** @param array<string, mixed>|null $formPage */
    private function editForm(Edit\Output $output, string $action, ?array $formPage = null, ?string $error = null, int $status = Response::HTTP_OK): Response
    {
        $page = $formPage ?? $output->page?->toArray();
        $pageIdValue = $page['id'] ?? null;
        $pageId = is_scalar($pageIdValue) ? (int) $pageIdValue : null;
        $versions = [];
        if ($pageId !== null) {
            $history = $this->history->execute(History\Input::create(pageId: $pageId));
            foreach ($history->versions as $version) {
                $versions[] = $version->toArray();
            }
        }
        $media = [];
        foreach ($output->media as $item) {
            $media[] = $item->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'page/edit.latte', params: [
            'title' => '固定ページ編集',
            'action' => $action,
            'csrf' => $this->csrf->token(),
            'page' => $page ?? ['status' => 'draft', 'body_format' => 'markdown'],
            'media' => $media,
            'versions' => $versions,
            'flash' => $this->session->pullFlash(key: 'success'),
            'error' => $error,
            'statuses' => [
                'draft' => '下書き',
                'published' => '公開',
                'private' => '非公開',
                'trashed' => 'ゴミ箱',
            ],
        ]), status: $status);
    }
}
