<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Security\LoginAttempt\Show;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\Security\Session;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $formValues = $this->session->pullFlashValue(key: 'security.login_attempt.form_values');

        return new Response(body: $this->renderer->render(template: 'security/login-attempts.latte', params: [
            'title' => 'セキュリティ',
            'formValues' => is_array($formValues) ? $formValues : [],
            'error' => $this->session->pullFlash(key: 'security.login_attempt.error'),
            'flash' => $this->session->pullFlash(key: 'success'),
            'csrf' => $this->csrf->token(),
        ]));
    }
}
