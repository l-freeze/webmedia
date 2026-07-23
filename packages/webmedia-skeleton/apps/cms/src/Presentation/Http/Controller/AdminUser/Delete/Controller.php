<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\AdminUser\Delete;

use App\Cms\Application\AdminUser\Delete\Input;
use App\Cms\Application\AdminUser\Delete\Interactor;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Cms\Presentation\Audit\AuditReporter;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $delete,
        private CmsGuard $guard,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();

        $userId = (int) $vars['id'];
        $output = $this->delete->execute(Input::create(id: $userId, currentUser: $currentUser));
        if (!$output->deleted) {
            $this->session->flash(key: 'admin_user.form_error', value: $output->error ?? '削除できませんでした。');

            return Response::redirect('/admin/users');
        }

        $this->audit->adminUserDeleted(request: $request, userId: $userId);
        $this->session->flash(key: 'success', value: '管理者アカウントを削除しました');
        return Response::redirect('/admin/users');
    }
}
