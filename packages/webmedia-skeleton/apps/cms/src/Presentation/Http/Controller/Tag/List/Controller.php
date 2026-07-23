<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Tag\List;

use App\Cms\Application\Tag\List\Input;
use App\Cms\Application\Tag\List\Interactor;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Csrf;
use Pera\Framework\Security\Session;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private Interactor $list,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(Input::create());
        $tags = [];
        foreach ($output->tags as $tag) {
            $tags[] = $tag->toArray();
        }
        $formValues = $this->session->pullFlashValue(key: 'tag.form_values');

        return new Response(body: $this->renderer->render(template: 'tag/index.latte', params: [
            'title' => 'タグ管理',
            'tags' => $tags,
            'formValues' => is_array($formValues) ? $formValues : [],
            'error' => $this->session->pullFlash(key: 'tag.form_error'),
            'flash' => $this->session->pullFlash(key: 'success'),
            'csrf' => $this->csrf->token(),
        ]));
    }
}
