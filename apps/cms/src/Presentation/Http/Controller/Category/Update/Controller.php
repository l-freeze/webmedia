<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Category\Update;

use App\Cms\Application\Category\Update\Input;
use App\Cms\Application\Category\Update\Interactor;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $update,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->update->execute(Input::create(
            id: (int) $vars['id'],
            name: $request->bodyInput('name'),
            slug: $request->bodyInput('slug'),
            description: $request->bodyInput('description'),
        ));
        if (!$output->updated) {
            $this->session->flash(key: 'category.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect('/admin/categories');
        }

        $this->audit->categorySaved(request: $request, categoryId: (int) $vars['id'], created: false);
        $this->session->flash(key: 'success', value: 'カテゴリを更新しました');
        return Response::redirect('/admin/categories');
    }
}
