<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\View\Renderer;
use App\Front\Application\Inquiry\Create\Input;
use App\Front\Application\Inquiry\Create\Interactor;

final readonly class ContactController
{
    public function __construct(
        private Renderer $renderer,
        private Csrf $csrf,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function create(Request $request, array $vars = []): Response
    {
        return $this->form();
    }

    /** @param array<string, string> $vars */
    public function store(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate($request->input('_csrf'))) {
            return $this->form('不正な送信です。', $request->body);
        }

        $name = $request->input('name');
        $email = $request->input('email');
        $subject = $request->input('subject');
        $message = $request->input('message');

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $subject === '' || $message === '') {
            return $this->form('入力内容を確認してください。', $request->body);
        }

        $this->interactor->execute(new Input($name, $email, $subject, $message));
        return Response::redirect('/contact/success');
    }

    /** @param array<string, string> $vars */
    public function success(Request $request, array $vars = []): Response
    {
        return new Response($this->renderer->render('contact/success.latte', [
            'title' => '問い合わせ完了',
            'siteName' => 'WEB Media',
        ]));
    }

    /** @param array<string, mixed> $old */
    private function form(?string $error = null, array $old = []): Response
    {
        return new Response($this->renderer->render('contact/form.latte', [
            'title' => '問い合わせ',
            'siteName' => 'WEB Media',
            'csrf' => $this->csrf->token(),
            'error' => $error,
            'old' => $old,
        ]), $error === null ? 200 : 422);
    }
}
