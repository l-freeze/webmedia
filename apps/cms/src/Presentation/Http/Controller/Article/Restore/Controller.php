<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Restore;

use App\Cms\Application\Article\Restore\Input;
use App\Cms\Application\Article\Restore\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;

final readonly class Controller
{
    public function __construct(
        private Interactor $restore,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $this->restore->execute(input: new Input(articleId: (int) $vars['id'], versionNo: (int) $vars['version']));

        return Response::redirect(location: '/admin/articles/' . $vars['id']);
    }
}
