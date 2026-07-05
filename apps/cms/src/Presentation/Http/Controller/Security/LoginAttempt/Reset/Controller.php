<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Security\LoginAttempt\Reset;

use App\Cms\Application\Auth\ResetLoginAttempt\Input;
use App\Cms\Application\Auth\ResetLoginAttempt\Interactor;
use App\Cms\Infrastructure\Security\CmsGuard;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private CmsGuard $guard,
        private Interactor $reset,
        private Session $session,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $currentUser = $this->guard->currentUser();

        $scope = $request->bodyInput(key: 'scope');
        $email = $request->bodyInput(key: 'email');
        $ipAddress = $request->bodyInput(key: 'ip_address');
        $reason = $request->bodyInput(key: 'reason');

        $output = $this->reset->execute(new Input(
            scope: $scope,
            email: $email,
            ipAddress: $ipAddress,
            resetByUserId: $currentUser->id,
            reason: $reason,
        ));
        if (!$output->reset) {
            $this->session->flash(key: 'security.login_attempt.error', value: $output->error ?? 'ログイン制限を解除できませんでした。');
            $this->session->flashValue(key: 'security.login_attempt.form_values', value: $request->body);

            return Response::redirect('/admin/security/login-attempts');
        }

        $this->audit->loginAttemptReset(request: $request, scope: $scope, ipAddress: $ipAddress);
        $this->session->flash(key: 'success', value: 'ログイン制限を解除しました。');

        return Response::redirect('/admin/security/login-attempts');
    }
}
