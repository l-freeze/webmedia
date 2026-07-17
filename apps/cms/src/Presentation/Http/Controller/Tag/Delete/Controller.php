<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Tag\Delete;

use App\Cms\Application\Tag\Delete\Input;
use App\Cms\Application\Tag\Delete\Interactor;
use App\Cms\Presentation\Audit\AuditReporter;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Session;

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
            $this->session->flash(key: 'tag.form_error', value: $output->error ?? '削除できませんでした');

            return Response::redirect('/admin/tags');
        }

        $this->audit->tagDeleted(request: $request, tagId: (int) $vars['id']);
        $this->session->flash(key: 'success', value: 'タグを削除しました');

        return Response::redirect('/admin/tags');
    }
}
