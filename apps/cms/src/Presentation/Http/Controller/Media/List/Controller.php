<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Media\List;

use App\Cms\Application\Media\List\Input;
use App\Cms\Application\Media\List\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private Interactor $interactor,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create());
        $media = [];
        foreach ($output->rows as $row) {
            $media[] = $row->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'media/index.latte', params: [
            'title' => 'メディア管理',
            'csrf' => $this->csrf->token(),
            'media' => $media,
            'flash' => $this->session->pullFlash(key: 'success'),
            'error' => $this->session->pullFlash(key: 'error'),
        ]));
    }
}
