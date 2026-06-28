<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Article\VersionPreview;

use App\Cms\Application\ArticleVersion\Preview\Input;
use App\Cms\Application\ArticleVersion\Preview\Interactor;
use App\Shared\Markdown\Infrastructure\Markdown;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Markdown $markdown,
        private Interactor $articleVersionPreview,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $articleVersion = $this->articleVersionPreview->execute(
            input: new Input(articleId: (int) $vars['id'], versionNo: (int) $vars['version'])
        )->articleVersion;
        if ($articleVersion === null) {
            return new Response(body: 'Not Found', status: Response::HTTP_NOT_FOUND);
        }

        return new Response(body: $this->renderer->render(template: 'article/version-preview.latte', params: [
            'title' => '履歴プレビュー',
            'articleVersion' => $articleVersion->toArray($this->markdown->bodyToSafeHtml(body: $articleVersion->body, format: $articleVersion->bodyFormat)),
        ]));
    }
}
