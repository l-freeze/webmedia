<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Page\Create;

use App\Cms\Application\Page\Edit\Input;
use App\Cms\Application\Page\Edit\Interactor;
use App\Cms\Application\Page\Edit\Output;
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
        private Interactor $edit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $formPage = $this->session->pullFlashValue(key: 'page.form_input');
        if (!is_array($formPage)) {
            $formPage = null;
        }
        /** @var array<string, mixed>|null $formPage */

        return $this->editForm(
            output: $this->edit->execute(Input::create(id: null)),
            action: '/admin/pages',
            formPage: $formPage,
            error: $this->session->pullFlash(key: 'page.form_error'),
        );
    }

    /** @param array<string, mixed>|null $formPage */
    private function editForm(Output $output, string $action, ?array $formPage = null, ?string $error = null, int $status = Response::HTTP_OK): Response
    {
        $page = $formPage ?? $output->page?->toArray();

        return new Response(body: $this->renderer->render(template: 'page/edit.latte', params: [
            'title' => '固定ページ作成',
            'action' => $action,
            'csrf' => $this->csrf->token(),
            'page' => $page ?? ['status' => 'draft', 'body_format' => 'markdown'],
            'media' => array_map(fn ($item): array => $item->toArray(), $output->media),
            'versions' => [],
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
