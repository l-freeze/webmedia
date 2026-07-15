<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Page\List;

use App\Cms\Application\Page\List\Input;
use App\Cms\Application\Page\List\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Session $session,
        private Interactor $list,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(Input::create());
        $pages = [];
        foreach ($output->rows as $row) {
            $pages[] = $row->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'page/index.latte', params: [
            'title' => '固定ページ管理',
            'pages' => $pages,
            'flash' => $this->session->pullFlash(key: 'success'),
        ]));
    }
}
