<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Auth\Login;

use App\Cms\Application\Auth\Login\Input;
use App\Cms\Application\Auth\Login\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private Interactor $login,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate(token: $request->input(key: '_csrf'))) {
            return $this->form(error: '不正な送信です。');
        }

        $output = $this->login->execute(input: new Input(
            email: $request->input(key: 'email'),
            password: $request->input(key: 'password'),
        ));
        if (!$output->authenticated) {
            return $this->form(error: 'メールアドレスまたはパスワードが違います。');
        }

        $this->session->regenerate();
        $this->session->set(key: 'cms.user_id', value: $output->userId);
        $this->session->set(key: 'cms.role', value: $output->role);

        return Response::redirect(location: '/admin');
    }

    private function form(string $error): Response
    {
        return new Response(body: $this->renderer->render(template: 'auth/login.latte', params: [
            'csrf' => $this->csrf->token(),
            'error' => $error,
        ]), status: 422);
    }
}
