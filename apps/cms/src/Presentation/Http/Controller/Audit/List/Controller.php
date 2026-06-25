<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Audit\List;

use App\Cms\Application\Audit\List\Input;
use App\Cms\Application\Audit\List\Interactor;
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
        $limit = (int) $request->queryInput(key: 'limit', default: '100');
        $output = $this->interactor->execute(input: new Input(limit: $limit));

        return new Response(body: $this->renderer->render(template: 'audit/index.latte', params: [
            'title' => '監査ログ',
            'rows' => $output->rows,
            'limit' => max(1, min(200, $limit)),
        ]));
    }
}
