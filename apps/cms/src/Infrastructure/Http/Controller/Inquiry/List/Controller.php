<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Inquiry\List;

use App\Cms\Application\Inquiry\List\Input;
use App\Cms\Application\Inquiry\List\Interactor;
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
        $output = $this->interactor->execute(input: new Input());

        return new Response(body: $this->renderer->render(template: 'simple-list.latte', params: [
            'title' => '問い合わせ管理',
            'heading' => '問い合わせ管理',
            'columns' => ['名前', 'メール', '件名', '状態', '受信日'],
            'rows' => array_map(fn ($row): array => $row->toArray(), $output->rows),
        ]));
    }
}
