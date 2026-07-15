<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Trash\List;

use App\Cms\Application\Trash\List\Input;
use App\Cms\Application\Trash\List\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create());
        $rows = [];
        foreach ($output->rows as $row) {
            $rows[] = $row->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'simple-list.latte', params: [
            'title' => 'ゴミ箱',
            'heading' => 'ゴミ箱',
            'columns' => ['タイトル', 'スラッグ', '削除日'],
            'rows' => $rows,
        ]));
    }
}
