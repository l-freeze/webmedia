<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\AdminUser\Update;

use App\Cms\Application\AdminUser\Update\Input;
use App\Cms\Application\AdminUser\Update\Interactor;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $update,
        private CmsGuard $guard,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();
        if ($currentUser === null) {
            return Response::redirect('/admin/login');
        }

        $userId = (int) $vars['id'];
        $output = $this->update->execute(new Input(id: $userId, data: $request->body, currentUser: $currentUser));
        if (!$output->updated) {
            $this->session->flash(key: 'admin_user.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect('/admin/users');
        }

        $this->audit->adminUserUpdated(request: $request, userId: $userId);
        $this->session->flash(key: 'success', value: '管理者アカウントを更新しました');
        return Response::redirect('/admin/users');
    }
}
