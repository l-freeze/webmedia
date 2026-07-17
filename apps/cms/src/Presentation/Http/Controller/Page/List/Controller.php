<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Page\List;

use App\Cms\Application\Page\List\Input;
use App\Cms\Application\Page\List\Interactor;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Session;
use Pera\Framework\View\ITemplateRenderer;

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
