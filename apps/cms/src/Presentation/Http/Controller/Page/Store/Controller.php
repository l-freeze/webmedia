<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Page\Store;

use App\Cms\Application\Page\Save\Input;
use App\Cms\Application\Page\Save\Interactor;
use App\Cms\Presentation\Audit\AuditTrailWriter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $save,
        private AuditTrailWriter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->save->execute(input: new Input(id: null, data: $request->body));
        if ($output->pageId === null) {
            $this->session->flashValue(key: 'page.form_input', value: $this->pageFormData(data: $request->body, id: null));
            $this->session->flash(key: 'page.form_error', value: $output->validation->firstMessage() ?? '入力内容を確認してください。');

            return Response::redirect(location: '/admin/pages/create');
        }

        $this->session->flash(key: 'success', value: '保存しました');
        $this->audit->pageSaved(request: $request, pageId: $output->pageId->value, created: true);
        return Response::redirect(location: '/admin/pages/' . $output->pageId->value);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function pageFormData(array $data, ?int $id): array
    {
        $status = $data['status'] ?? 'draft';
        $data['status'] = is_scalar($status) ? (string) $status : 'draft';
        if ($id !== null) {
            $data['id'] = $id;
        }

        return $data;
    }
}
