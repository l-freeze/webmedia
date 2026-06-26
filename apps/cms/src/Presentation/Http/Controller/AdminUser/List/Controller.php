<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\AdminUser\List;

use App\Cms\Application\AdminUser\List\Input;
use App\Cms\Application\AdminUser\List\Interactor;
use App\Cms\Application\Auth\Authorization\Value\RoleCode;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Csrf $csrf,
        private Session $session,
        private Interactor $list,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->list->execute(input: new Input());
        $users = [];
        foreach ($output->users as $user) {
            $users[] = $user->toArray();
        }

        $formValues = $this->session->pullFlashValue(key: 'admin_user.form_values');

        return new Response(body: $this->renderer->render(template: 'admin-user/index.latte', params: [
            'title' => '管理者',
            'users' => $users,
            'roles' => RoleCode::all(),
            'formValues' => is_array($formValues) ? $formValues : [],
            'error' => $this->session->pullFlash(key: 'admin_user.form_error'),
            'flash' => $this->session->pullFlash(key: 'success'),
            'csrf' => $this->csrf->token(),
        ]));
    }
}
