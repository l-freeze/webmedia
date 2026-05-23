<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;
use App\Front\Application\Article\Show\Input;
use App\Front\Application\Article\Show\Interactor;

final readonly class ArticleController
{
    public function __construct(private Renderer $renderer, private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(new Input($vars['slug'] ?? ''));
        $article = $output->article;
        if ($article === null) {
            return new Response($this->renderer->render('state/simple.latte', [
                'title' => '404',
                'siteName' => 'WEB Media',
                'heading' => 'ページが見つかりません',
                'message' => '指定された記事は見つかりませんでした。',
            ]), 404);
        }

        $article = $article->toArray();
        return new Response($this->renderer->render('article/show.latte', [
            'title' => $article['title'],
            'siteName' => 'WEB Media',
            'description' => $article['excerpt'],
            'article' => $article,
            'related' => array_map(fn ($item): array => $item->toArray(), $output->related),
        ]));
    }
}
