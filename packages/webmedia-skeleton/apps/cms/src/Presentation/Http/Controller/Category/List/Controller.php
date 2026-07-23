<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Category\List;

use App\Cms\Application\Category\List\Input;
use App\Cms\Application\Category\List\Interactor;
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
        $categories = [];
        foreach ($output->categories as $category) {
            $categories[] = $category->toArray();
        }
        $formValues = $this->session->pullFlashValue(key: 'category.form_values');

        return new Response(body: $this->renderer->render(template: 'category/index.latte', params: [
            'title' => 'カテゴリ管理',
            'categories' => $categories,
            'formValues' => is_array($formValues) ? $formValues : [],
            'error' => $this->session->pullFlash(key: 'category.form_error'),
            'flash' => $this->session->pullFlash(key: 'success'),
            'csrf' => $this->csrf->token(),
        ]));
    }
}
