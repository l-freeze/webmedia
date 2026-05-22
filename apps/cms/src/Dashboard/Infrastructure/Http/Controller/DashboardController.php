<?php

declare(strict_types=1);

namespace App\Cms\Dashboard\Infrastructure\Http\Controller;

use App\Cms\Dashboard\Query\ShowDashboard\DashboardQueryService;
use App\Cms\Infrastructure\Http\AuthGuard;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class DashboardController
{
    use AuthGuard;

    public function __construct(private Renderer $renderer, private Session $session, private DashboardQueryService $dashboard)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }

        return new Response($this->renderer->render('dashboard/index.latte', [
            'title' => 'ダッシュボード',
            'stats' => $this->dashboard->stats(),
        ]));
    }
}
