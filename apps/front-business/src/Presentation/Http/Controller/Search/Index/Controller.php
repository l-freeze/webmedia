<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Search\Index;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\View\ITemplateRenderer;
use App\FrontBusiness\Application\Article\Search\Input;
use App\FrontBusiness\Application\Article\Search\Interactor;
use App\FrontBusiness\Application\Layout\Load\Query\ISiteSettingQuery;

final readonly class Controller
{
    public function __construct(private ITemplateRenderer $renderer, private Interactor $interactor, private ISiteSettingQuery $settings)
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

        $output = $this->interactor->execute(Input::create(keyword: $request->input(key: 'q')));

        return new Response(body: $this->renderer->render(template: 'list.latte', params: [
            'title' => '検索結果',
            'heading' => $output->keyword === '' ? '検索' : "「{$output->keyword}」の検索結果",
            'lead' => count($output->articles) . '件の記事が見つかりました。',
            'articles' => array_map(fn ($article): array => $article->toArray(), $output->articles),
            'query' => $output->keyword,
        ]));
    }
}
