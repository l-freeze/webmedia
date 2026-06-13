<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Tag\Delete;

use App\Cms\Application\Tag\Delete\Input;
use App\Cms\Application\Tag\Delete\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $delete,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->delete->execute(input: new Input(id: (int) $vars['id']));
        if (!$output->deleted) {
            $this->session->flash(key: 'tag.form_error', value: $output->error ?? '削除できませんでした');

            return Response::redirect(location: '/admin/tags');
        }

        $this->session->flash(key: 'success', value: 'タグを削除しました');

        return Response::redirect(location: '/admin/tags');
    }
}
