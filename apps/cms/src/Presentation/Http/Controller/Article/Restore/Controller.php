<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Restore;

use App\Cms\Application\Article\Restore\Input;
use App\Cms\Application\Article\Restore\Interactor;
use App\Cms\Domain\Exception\ResourceOperationDeniedException;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class Controller
{
    public function __construct(
        private CmsGuard $guard,
        private Interactor $restore,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();

        try {
            $this->restore->execute(Input::create(articleId: (int) $vars['id'], versionNo: (int) $vars['version'], currentUser: $currentUser));
        } catch (ResourceOperationDeniedException) {
            return new Response(body: 'Forbidden', status: Response::HTTP_FORBIDDEN);
        }

        $this->audit->articleRestored(request: $request, articleId: (int) $vars['id'], versionNo: (int) $vars['version']);

        return Response::redirect('/admin/articles/' . $vars['id']);
    }
}
