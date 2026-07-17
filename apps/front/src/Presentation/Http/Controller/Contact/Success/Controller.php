<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Contact\Success;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(private ITemplateRenderer $renderer)
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
