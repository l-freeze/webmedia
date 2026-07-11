<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Category\Delete;

use App\Cms\Application\Category\Delete\Input;
use App\Cms\Application\Category\Delete\Interactor;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $delete,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->delete->execute(Input::create(id: (int) $vars['id']));
        if (!$output->deleted) {
            $this->session->flash(key: 'category.form_error', value: $output->error ?? '削除できませんでした');

            return Response::redirect('/admin/categories');
        }

        $this->audit->categoryDeleted(request: $request, categoryId: (int) $vars['id']);
        $this->session->flash(key: 'success', value: 'カテゴリを削除しました');

        return Response::redirect('/admin/categories');
    }
}
