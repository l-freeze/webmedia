<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Search\Index;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;
use App\Front\Application\Article\Search\Input;
use App\Front\Application\Article\Search\Interactor;
use App\Front\Application\Layout\Query\ISiteSettingQuery;

final readonly class Controller
{
    public function __construct(private TemplateRenderer $renderer, private Interactor $interactor, private ISiteSettingQuery $settings)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->settings->current()->searchEnabled) {
            return new Response(body: $this->renderer->render(template: 'state/simple.latte', params: [
                'title' => '検索停止中',
                'heading' => '検索機能は現在停止中です',
                'message' => 'カテゴリ一覧またはタグ一覧から記事を探してください。',
            ]), status: Response::HTTP_NOT_FOUND);
        }

        $output = $this->interactor->execute(new Input(keyword: $request->input(key: 'q')));

        return new Response(body: $this->renderer->render(template: 'list.latte', params: [
            'title' => '検索結果',
            'heading' => $output->keyword === '' ? '検索' : "「{$output->keyword}」の検索結果",
            'lead' => count($output->articles) . '件の記事が見つかりました。',
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
            'query' => $output->keyword,
        ]));
    }
}
