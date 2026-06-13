<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Page\List;

use App\Cms\Application\Page\List\Input;
use App\Cms\Application\Page\List\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Session $session,
        private Interactor $list,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(input: new Input());

        return new Response(body: $this->renderer->render(template: 'page/index.latte', params: [
            'title' => '固定ページ管理',
            'pages' => array_map(fn ($row): array => $row->toArray(), $output->rows),
            'flash' => $this->session->pullFlash(key: 'success'),
        ]));
    }
}
