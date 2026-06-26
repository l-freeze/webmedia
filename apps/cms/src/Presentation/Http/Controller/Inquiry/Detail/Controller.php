<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Inquiry\Detail;

use App\Cms\Application\Inquiry\Detail\Input;
use App\Cms\Application\Inquiry\Detail\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(input: new Input(id: (int) ($vars['id'] ?? 0)));
        if ($output->inquiry === null) {
            return new Response(body: 'Not Found', status: Response::HTTP_NOT_FOUND);
        }

        return new Response(body: $this->renderer->render(template: 'inquiry/detail.latte', params: [
            'title' => '問い合わせ詳細',
            'inquiry' => $output->inquiry,
        ]));
    }
}
