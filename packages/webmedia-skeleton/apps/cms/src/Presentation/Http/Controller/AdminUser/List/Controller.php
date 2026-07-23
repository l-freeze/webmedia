<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\AdminUser\List;

use App\Cms\Application\AdminUser\List\Input;
use App\Cms\Application\AdminUser\List\Interactor;
use App\Cms\Domain\Value\CmsRole;
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
        $users = [];
        foreach ($output->users as $user) {
            $users[] = $user->toArray();
        }

        $formValues = $this->session->pullFlashValue(key: 'admin_user.form_values');
        $roles = [];
        foreach (CmsRole::cases() as $role) {
            $roles[] = $role->value;
        }

        return new Response(body: $this->renderer->render(template: 'admin-user/index.latte', params: [
            'title' => '管理者',
            'users' => $users,
            'roles' => $roles,
            'formValues' => is_array($formValues) ? $formValues : [],
            'error' => $this->session->pullFlash(key: 'admin_user.form_error'),
            'flash' => $this->session->pullFlash(key: 'success'),
            'csrf' => $this->csrf->token(),
        ]));
    }
}
