<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Setting\Update;

use App\Cms\Application\Setting\Save\Input;
use App\Cms\Application\Setting\Save\Interactor;
use App\Cms\Presentation\Audit\CmsAuditService;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $save,
        private CmsAuditService $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $this->save->execute(input: new Input(data: $request->body));
        $this->audit->settingsUpdated(request: $request);

        $this->session->flash(key: 'success', value: '保存しました');

        return Response::redirect(location: '/admin/settings');
    }
}
