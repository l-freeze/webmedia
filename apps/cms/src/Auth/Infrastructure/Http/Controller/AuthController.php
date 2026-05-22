<?php

declare(strict_types=1);

namespace App\Cms\Auth\Infrastructure\Http\Controller;

use App\Cms\Auth\Domain\UserRepository;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class AuthController
{
    public function __construct(
        private Renderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private UserRepository $users,
    ) {
    }

    /** @param array<string, string> $vars */
    public function loginForm(Request $request, array $vars = []): Response
    {
        return $this->form();
    }

    /** @param array<string, string> $vars */
    public function login(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate($request->input('_csrf'))) {
            return $this->form('不正な送信です。');
        }

        $user = $this->users->findByEmail($request->input('email'));
        if ($user === null || !password_verify($request->input('password'), $user['password_hash'])) {
            return $this->form('メールアドレスまたはパスワードが違います。');
        }

        $this->session->regenerate();
        $this->session->set('cms.user_id', (int) $user['id']);
        $this->session->set('cms.role', $user['role']);
        return Response::redirect('/admin');
    }

    /** @param array<string, string> $vars */
    public function logout(Request $request, array $vars = []): Response
    {
        $this->session->destroy();
        return Response::redirect('/admin/login');
    }

    private function form(?string $error = null): Response
    {
        return new Response($this->renderer->render('auth/login.latte', [
            'csrf' => $this->csrf->token(),
            'error' => $error,
        ]), $error === null ? 200 : 422);
    }
}
