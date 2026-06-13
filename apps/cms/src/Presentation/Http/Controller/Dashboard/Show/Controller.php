<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Dashboard\Show;

use App\Cms\Application\Dashboard\Show\Input;
use App\Cms\Application\Dashboard\Show\Interactor;
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

        return new Response(body: $this->renderer->render(template: 'dashboard/index.latte', params: [
            'title' => 'ダッシュボード',
            'stats' => $output->stats->toArray(),
        ]));
    }
}
