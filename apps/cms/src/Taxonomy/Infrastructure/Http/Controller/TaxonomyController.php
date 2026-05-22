<?php

declare(strict_types=1);

namespace App\Cms\Taxonomy\Infrastructure\Http\Controller;

use App\Cms\Infrastructure\Http\AuthGuard;
use App\Cms\Taxonomy\Domain\TaxonomyRepository;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class TaxonomyController
{
    use AuthGuard;

    public function __construct(private Renderer $renderer, private Session $session, private TaxonomyRepository $taxonomy)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $rows = array_merge($this->taxonomy->categories(), $this->taxonomy->tags());
        return new Response($this->renderer->render('simple-list.latte', [
            'title' => 'カテゴリ/タグ管理',
            'heading' => 'カテゴリ/タグ管理',
            'columns' => ['名前', 'スラッグ', '説明'],
            'rows' => $rows,
            'form' => [['label' => 'カテゴリ名', 'name' => 'name'], ['label' => 'スラッグ', 'name' => 'slug'], ['label' => '説明', 'name' => 'description']],
        ]));
    }

    /** @param array<string, string> $vars */
    public function store(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $this->taxonomy->createCategory($request->body);
        return Response::redirect('/admin/taxonomy');
    }
}
