<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Article\History;

use App\Cms\Application\Article\History\Input;
use App\Cms\Application\Article\History\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class Controller
{
    public function __construct(
        private Renderer $renderer,
        private Session $session,
        private Interactor $history,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->session->has(key: 'cms.user_id')) {
            return Response::redirect(location: '/admin/login');
        }

        $output = $this->history->execute(input: new Input(articleId: (int) $vars['id']));

        return new Response(body: $this->renderer->render(template: 'article/history.latte', params: [
            'title' => '更新履歴',
            'articleId' => $output->articleId,
            'versions' => array_map(fn ($version): array => $version->toArray(), $output->versions),
        ]));
    }
}
