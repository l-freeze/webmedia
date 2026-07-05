<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Delete;

use App\Cms\Application\Article\Delete\Input;
use App\Cms\Application\Article\Delete\Interactor;
use App\Cms\Domain\Exception\ResourceOperationDeniedException;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private CmsGuard $guard,
        private Interactor $delete,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();

        try {
            $output = $this->delete->execute(new Input(id: (int) $vars['id'], currentUser: $currentUser));
        } catch (ResourceOperationDeniedException) {
            return new Response(body: 'Forbidden', status: Response::HTTP_FORBIDDEN);
        }

        $this->session->flash(key: 'success', value: $output->deleted ? '記事をゴミ箱へ移動しました' : ($output->error ?? '記事を削除できませんでした'));
        if ($output->deleted) {
            $this->audit->articleTrashed(request: $request, articleId: (int) $vars['id']);
        }

        return Response::redirect('/admin/articles');
    }
}
