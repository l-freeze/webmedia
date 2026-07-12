<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Inquiry\Detail;

use App\Cms\Application\Inquiry\Detail\Input;
use App\Cms\Application\Inquiry\Detail\Interactor;
use App\Cms\Domain\Value\InquiryStatus;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $interactor,
        private Session $session,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create(id: (int) ($vars['id'] ?? 0)));
        if ($output->inquiry === null) {
            return new Response(body: 'Not Found', status: Response::HTTP_NOT_FOUND);
        }
        $formInput = $this->session->pullFlashValue(key: 'inquiry.form_input');
        if (!is_array($formInput)) {
            $formInput = [];
        }

        return new Response(body: $this->renderer->render(template: 'inquiry/detail.latte', params: [
            'title' => '問い合わせ詳細',
            'inquiry' => $output->inquiry,
            'formInput' => $formInput,
            'statusOptions' => InquiryStatus::labels(),
            'flash' => $this->session->pullFlash(key: 'success'),
            'error' => $this->session->pullFlash(key: 'inquiry.form_error'),
        ]));
    }
}
