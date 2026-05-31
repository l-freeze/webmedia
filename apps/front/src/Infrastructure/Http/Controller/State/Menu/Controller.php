<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller\State\Menu;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;

final readonly class Controller
{
    public function __construct(private Renderer $renderer)
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
