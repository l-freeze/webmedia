<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Taxonomy\List;

use App\Cms\Application\Taxonomy\List\Input;
use App\Cms\Application\Taxonomy\List\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class Controller
{
    public function __construct(
        private Renderer $renderer,
        private Session $session,
        private Csrf $csrf,
        private Interactor $list,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->session->has(key: 'cms.user_id')) {
            return Response::redirect(location: '/admin/login');
        }

        $output = $this->list->execute(input: new Input());
        $rows = array_map(fn ($row): array => $row->toArray(), $output->rows);
        $categories = array_values(array_filter($rows, fn (array $row): bool => $row['type'] === 'category'));
        $tags = array_values(array_filter($rows, fn (array $row): bool => $row['type'] === 'tag'));
        $formValues = $this->session->pullFlashValue(key: 'taxonomy.form_values');

        return new Response(body: $this->renderer->render(template: 'taxonomy/index.latte', params: [
            'title' => 'カテゴリ/タグ管理',
            'categories' => $categories,
            'tags' => $tags,
            'formValues' => is_array($formValues) ? $formValues : [],
            'error' => $this->session->pullFlash(key: 'taxonomy.form_error'),
            'flash' => $this->session->pullFlash(key: 'success'),
            'csrf' => $this->csrf->token(),
        ]));
    }
}
