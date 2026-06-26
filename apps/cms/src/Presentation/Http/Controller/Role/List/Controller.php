<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Role\List;

use App\Cms\Application\Role\List\Input;
use App\Cms\Application\Role\List\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Interactor $list,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(input: new Input());
        $roles = [];
        foreach ($output->roles as $role) {
            $roles[] = $role->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'role/index.latte', params: [
            'title' => 'ロール',
            'roles' => $roles,
        ]));
    }
}
