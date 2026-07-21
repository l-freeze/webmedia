<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Presentation\Http\Controller\Todo\Index;

use App\{{ namespace }}\Application\Todo\ITodoRepository;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITodoRepository $todos,
        private ITemplateRenderer $renderer,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $items = [];
        foreach ($this->todos->all() as $todo) {
            $items[] = $todo->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'index.latte', params: [
            'title' => '{{ name }} todos',
            'basePath' => '{{ viewBasePath }}',
            'todos' => $items,
        ]));
    }
}
