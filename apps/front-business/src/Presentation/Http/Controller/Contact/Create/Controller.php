<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Contact\Create;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\View\ITemplateRenderer;
use App\FrontBusiness\Application\Inquiry\Create\InquiryInputLimits;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'contact/form.latte', params: [
            'title' => '問い合わせ',
            'csrf' => $this->csrf->token(),
            'error' => null,
            'old' => [],
            'limits' => $this->limits(),
        ]));
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
