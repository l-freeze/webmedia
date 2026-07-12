<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\Preview;

use App\Cms\Application\Article\Preview\Input;
use App\Cms\Application\Article\Preview\Interactor;
use App\Cms\Application\Article\PublicationDateTime;
use App\Cms\Presentation\Presenter\Article\ArticleStatusPresenter;
use App\Shared\Markdown\Infrastructure\Markdown;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Markdown $markdown,
        private Interactor $preview,
        private ArticleStatusPresenter $articleStatus,
        private PublicationDateTime $publicationDateTime,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $article = $this->preview->execute(Input::create(id: (int) $vars['id']))->article;
        if ($article === null) {
            return new Response(body: 'Not Found', status: Response::HTTP_NOT_FOUND);
        }

        return new Response(body: $this->renderer->render(template: 'article/preview.latte', params: [
            'title' => 'プレビュー',
            'article' => [
                ...$article->toArray($this->markdown->bodyToSafeHtml(body: $article->body, format: $article->bodyFormat)),
                'status_label' => $this->articleStatus->label(
                    status: $article->status,
                    publishedAt: $article->publishedAt,
                    publishedEndsAt: $article->publishedEndsAt,
                ),
                'published_at' => $this->publicationDateTime->toEditor($article->publishedAt),
                'published_ends_at' => $this->publicationDateTime->toEditor($article->publishedEndsAt),
            ],
        ]));
    }
}
