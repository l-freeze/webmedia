<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Permission\List;

use App\Cms\Application\Permission\List\Input;
use App\Cms\Application\Permission\List\Interactor;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Interactor $list,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(Input::create());
        $permissions = [];
        foreach ($output->permissions as $permission) {
            $permissions[] = $permission->toArray();
        }

        return new Response(body: $this->renderer->render(template: 'permission/index.latte', params: [
            'title' => 'パーミッション',
            'permissions' => $permissions,
        ]));
    }
}
