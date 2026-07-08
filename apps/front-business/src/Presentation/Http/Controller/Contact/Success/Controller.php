<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Contact\Success;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(private TemplateRenderer $renderer)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'contact/success.latte', params: [
            'title' => '問い合わせ完了',
        ]));
    }
}
