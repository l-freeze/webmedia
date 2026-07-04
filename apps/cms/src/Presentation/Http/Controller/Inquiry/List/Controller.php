<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Inquiry\List;

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
        $output = $this->interactor->execute(new Input(page: (int) $request->queryInput(key: 'page', default: '1')));

        return new Response(body: $this->renderer->render(template: 'inquiry/index.latte', params: [
            'title' => '問い合わせ管理',
            'inquiries' => $output->rows,
            'pagination' => [
                'current_page' => $output->currentPage,
                'per_page' => $output->perPage,
                'total_items' => $output->totalItems,
                'total_pages' => $output->totalPages,
                'previous_page' => $output->previousPage(),
                'next_page' => $output->nextPage(),
            ],
        ]));
    }
}
