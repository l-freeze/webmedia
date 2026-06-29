<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Restore;

use App\Cms\Application\Article\Restore\Input;
use App\Cms\Application\Article\Restore\Interactor;
use App\Cms\Application\Auth\Authorization\Exception\AuthorizationException;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Cms\Presentation\Audit\CmsAuditService;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class Controller
{
    public function __construct(
        private CmsGuard $guard,
        private Interactor $restore,
        private CmsAuditService $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();
        if ($currentUser === null) {
            return Response::redirect(location: '/admin/login');
        }

        try {
            $this->restore->execute(input: new Input(articleId: (int) $vars['id'], versionNo: (int) $vars['version'], currentUser: $currentUser));
        } catch (AuthorizationException) {
            return new Response(body: 'Forbidden', status: Response::HTTP_FORBIDDEN);
        }

        $this->audit->articleRestored(request: $request, articleId: (int) $vars['id'], versionNo: (int) $vars['version']);

        return Response::redirect(location: '/admin/articles/' . $vars['id']);
    }
}
