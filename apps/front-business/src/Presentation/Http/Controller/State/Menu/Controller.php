<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\State\Menu;

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
        return new Response(body: $this->renderer->render(template: 'state/simple.latte', params: [
            'title' => 'メニュー',
            'heading' => 'メニュー',
            'message' => 'カテゴリ、タグ、固定ページ、問い合わせへの導線をまとめています。',
        ]));
    }
}
