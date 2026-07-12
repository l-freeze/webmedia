<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\VersionPreview;

use App\Cms\Application\ArticleVersion\Preview\Input;
use App\Cms\Application\ArticleVersion\Preview\Interactor;
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
        private Interactor $articleVersionPreview,
        private ArticleStatusPresenter $articleStatus,
        private PublicationDateTime $publicationDateTime,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $articleVersion = $this->articleVersionPreview->execute(Input::create(
            articleId: (int) $vars['id'],
            versionNo: (int) $vars['version'],
        ))->articleVersion;
        if ($articleVersion === null) {
            return new Response(body: 'Not Found', status: Response::HTTP_NOT_FOUND);
        }

        return new Response(body: $this->renderer->render(template: 'article/version-preview.latte', params: [
            'title' => '履歴プレビュー',
            'articleVersion' => [
                ...$articleVersion->toArray($this->markdown->bodyToSafeHtml(body: $articleVersion->body, format: $articleVersion->bodyFormat)),
                'status_label' => $this->articleStatus->label(
                    status: $articleVersion->status,
                    publishedAt: $articleVersion->publishedAt,
                    publishedEndsAt: $articleVersion->publishedEndsAt,
                ),
                'published_at' => $this->publicationDateTime->toEditor($articleVersion->publishedAt),
                'published_ends_at' => $this->publicationDateTime->toEditor($articleVersion->publishedEndsAt),
            ],
        ]));
    }
}
