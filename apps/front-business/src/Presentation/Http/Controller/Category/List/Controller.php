<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Category\List;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;
use App\FrontBusiness\Application\Category\List\Input;
use App\FrontBusiness\Application\Category\List\Interactor;
use App\FrontBusiness\Presentation\BasePath;

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
        $output = $this->interactor->execute(new Input());

        return new Response(body: $this->renderer->render(template: 'taxonomy/index.latte', params: [
            'title' => 'カテゴリ一覧',
            'heading' => 'カテゴリ一覧',
            'itemBasePath' => BasePath::path(path: '/categories'),
            'items' => array_map(fn ($item): array => $item->toArray(), $output->items),
        ]));
    }
}
