<?php

declare(strict_types=1);

namespace App\Cms\Trash\Infrastructure\Http\Controller;

use App\Cms\Infrastructure\Http\AuthGuard;
use App\Cms\Trash\Query\ListTrash\TrashQueryService;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class TrashController
{
    use AuthGuard;

    public function __construct(private Renderer $renderer, private Session $session, private TrashQueryService $trash)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        return new Response($this->renderer->render('simple-list.latte', [
            'title' => 'ゴミ箱',
            'heading' => 'ゴミ箱',
            'columns' => ['タイトル', 'スラッグ', '削除日'],
            'rows' => $this->trash->list(),
        ]));
    }
}
