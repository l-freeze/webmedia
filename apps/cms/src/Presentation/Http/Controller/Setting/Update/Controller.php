<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Setting\Update;

use App\Cms\Application\Setting\Save\Input;
use App\Cms\Application\Setting\Save\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Csrf;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Csrf $csrf,
        private Interactor $save,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->csrf->validate(token: $request->bodyInput(key: '_csrf'))) {
            return new Response(body: 'Invalid CSRF token', status: 422);
        }

        $this->save->execute(input: new Input(data: $request->body));
        $this->session->flash(key: 'success', value: '保存しました');

        return Response::redirect(location: '/admin/settings');
    }
}
