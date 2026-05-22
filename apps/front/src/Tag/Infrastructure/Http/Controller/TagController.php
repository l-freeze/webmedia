<?php

declare(strict_types=1);

namespace App\Front\Tag\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Tag\Domain\TagQueryService;

final readonly class TagController
{
    public function __construct(private Renderer $renderer, private TagQueryService $tags)
    {
    }

    /** @param array<string, string> $vars */
    public function index(Request $request, array $vars = []): Response
    {
        return new Response($this->renderer->render('taxonomy/index.latte', [
            'title' => 'タグ一覧',
            'siteName' => 'WEB Media',
            'heading' => 'タグ一覧',
            'basePath' => '/tags',
            'items' => $this->tags->all(),
        ]));
    }

    /** @param array<string, string> $vars */
    public function show(Request $request, array $vars = []): Response
    {
        $tag = $this->tags->find($vars['slug'] ?? '');
        return new Response($this->renderer->render('list.latte', [
            'title' => $tag['name'] ?? 'タグ',
            'siteName' => 'WEB Media',
            'heading' => '#' . ($tag['name'] ?? 'タグ'),
            'articles' => $this->tags->articles($vars['slug'] ?? ''),
        ]));
    }
}
