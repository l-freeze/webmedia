<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Application\Article\Search\Input;
use App\Front\Application\Article\Search\Interactor;

final readonly class SearchController
{
    public function __construct(private Renderer $renderer, private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(new Input($request->input('q')));

        return new Response($this->renderer->render('list.latte', [
            'title' => '検索結果',
            'siteName' => 'WEB Media',
            'heading' => $output->keyword === '' ? '検索' : "「{$output->keyword}」の検索結果",
            'lead' => count($output->articles) . '件の記事が見つかりました。',
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
            'query' => $output->keyword,
        ]));
    }
}
