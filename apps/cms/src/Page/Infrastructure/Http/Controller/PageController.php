<?php

declare(strict_types=1);

namespace App\Cms\Page\Infrastructure\Http\Controller;

use App\Cms\Infrastructure\Http\AuthGuard;
use App\Cms\Page\Domain\PageRepository;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class PageController
{
    use AuthGuard;

    public function __construct(private Renderer $renderer, private Session $session, private PageRepository $pages)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        return new Response($this->renderer->render('simple-list.latte', [
            'title' => '固定ページ管理',
            'heading' => '固定ページ管理',
            'columns' => ['タイトル', 'スラッグ', '状態', '更新日'],
            'rows' => $this->pages->list(),
            'form' => [['label' => 'ページタイトル', 'name' => 'title'], ['label' => 'スラッグ', 'name' => 'slug']],
        ]));
    }

    /** @param array<string, string> $vars */
    public function store(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $this->pages->create($request->body);
        return Response::redirect('/admin/pages');
    }
}
