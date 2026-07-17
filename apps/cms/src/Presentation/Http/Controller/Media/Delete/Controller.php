<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Media\Delete;

use App\Cms\Application\Media\Delete\Input;
use App\Cms\Application\Media\Delete\Interactor;
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
        $output = $this->delete->execute(Input::create(id: (int) ($vars['id'] ?? 0)));
        if (!$output->deleted) {
            $this->session->flash(key: 'error', value: $output->error ?? 'メディア削除に失敗しました。');
            return Response::redirect('/admin/media');
        }

        $this->audit->mediaDeleted(request: $request, mediaId: (int) ($vars['id'] ?? 0));
        $this->session->flash(key: 'success', value: 'メディアを削除しました');
        return Response::redirect('/admin/media');
    }
}
