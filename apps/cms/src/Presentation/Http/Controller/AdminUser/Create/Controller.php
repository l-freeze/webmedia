<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\AdminUser\Create;

use App\Cms\Application\AdminUser\Create\Input;
use App\Cms\Application\AdminUser\Create\Interactor;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $create,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->create->execute(Input::create(
            email: $request->bodyInput('email'),
            name: $request->bodyInput('name'),
            password: $request->bodyInput('password'),
            role: $request->bodyInput('role'),
        ));
        if (!$output->created) {
            $this->session->flashValue(key: 'admin_user.form_values', value: $request->body);
            $this->session->flash(key: 'admin_user.form_error', value: $output->error ?? '入力内容を確認してください。');

            return Response::redirect('/admin/users');
        }

        $this->audit->adminUserCreated(request: $request, userId: $output->userId);
        $this->session->flash(key: 'success', value: '管理者アカウントを作成しました');

        return Response::redirect('/admin/users');
    }
}
