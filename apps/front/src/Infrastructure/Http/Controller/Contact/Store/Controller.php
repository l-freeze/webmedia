<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller\Contact\Store;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\View\TemplateRenderer;
use App\Front\Application\Inquiry\Create\Input;
use App\Front\Application\Inquiry\Create\Interactor;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Csrf $csrf,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate(token: $request->input(key: '_csrf'))) {
            return $this->form(error: '不正な送信です。', old: $request->body);
        }

        $name = $request->input(key: 'name');
        $email = $request->input(key: 'email');
        $subject = $request->input(key: 'subject');
        $message = $request->input(key: 'message');

        $output = $this->interactor->execute(input: new Input(name: $name, email: $email, subject: $subject, message: $message));
        if (!$output->created) {
            return $this->form(error: $output->error ?? '入力内容を確認してください。', old: $request->body);
        }

        return Response::redirect(location: '/contact/success');
    }

    /** @param array<string, mixed> $old */
    private function form(?string $error = null, array $old = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'contact/form.latte', params: [
            'title' => '問い合わせ',
            'csrf' => $this->csrf->token(),
            'error' => $error,
            'old' => $old,
        ]), status: $error === null ? 200 : 422);
    }
}
