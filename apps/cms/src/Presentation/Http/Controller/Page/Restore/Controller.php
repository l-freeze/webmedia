<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Page\Restore;

use App\Cms\Application\Page\Restore\Input;
use App\Cms\Application\Page\Restore\Interactor;
use App\Cms\Presentation\Audit\AuditReporter;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class Controller
{
    public function __construct(
        private Interactor $restore,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $this->restore->execute(Input::create(pageId: (int) $vars['id'], versionNo: (int) $vars['version']));
        $this->audit->pageRestored(request: $request, pageId: (int) $vars['id'], versionNo: (int) $vars['version']);

        return Response::redirect('/admin/pages/' . $vars['id']);
    }
}
