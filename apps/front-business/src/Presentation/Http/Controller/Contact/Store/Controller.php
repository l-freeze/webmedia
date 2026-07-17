<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Contact\Store;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\View\ITemplateRenderer;
use App\FrontBusiness\Application\Inquiry\Create\Input;
use App\FrontBusiness\Application\Inquiry\Create\InquiryInputLimits;
use App\FrontBusiness\Application\Inquiry\Create\Interactor;
use App\FrontBusiness\Presentation\BasePath;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $name = $request->input(key: 'name');
        $email = $request->input(key: 'email');
        $subject = $request->input(key: 'subject');
        $message = $request->input(key: 'message');

        $output = $this->interactor->execute(Input::create(name: $name, email: $email, subject: $subject, message: $message));
        if (!$output->created) {
            return $this->form(error: $output->error ?? '入力内容を確認してください。', old: $request->body);
        }

        return Response::redirect(BasePath::path(path: '/contact/success'));
    }

    /** @param array<string, mixed> $old */
    private function form(?string $error = null, array $old = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'contact/form.latte', params: [
            'title' => '問い合わせ',
            'csrf' => $this->csrf->token(),
            'error' => $error,
            'old' => $old,
            'limits' => $this->limits(),
        ]), status: $error === null ? Response::HTTP_OK : Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /** @return array{name:int,subject:int,message:int} */
    private function limits(): array
    {
        return [
            'name' => InquiryInputLimits::NAME_MAX_LENGTH,
            'subject' => InquiryInputLimits::SUBJECT_MAX_LENGTH,
            'message' => InquiryInputLimits::MESSAGE_MAX_LENGTH,
        ];
    }
}
