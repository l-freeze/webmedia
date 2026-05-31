<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Article\VersionPreview;

use App\Cms\Application\ArticleVersion\Preview\Input;
use App\Cms\Application\ArticleVersion\Preview\Interactor;
use App\Framework\Content\Markdown;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class Controller
{
    public function __construct(
        private Renderer $renderer,
        private Session $session,
        private Markdown $markdown,
        private Interactor $articleVersionPreview,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if (!$this->session->has(key: 'cms.user_id')) {
            return Response::redirect(location: '/admin/login');
        }

        $articleVersion = $this->articleVersionPreview->execute(
            input: new Input(articleId: (int) $vars['id'], versionNo: (int) $vars['version'])
        )->articleVersion;
        if ($articleVersion === null) {
            return new Response(body: 'Not Found', status: 404);
        }

        return new Response(body: $this->renderer->render(template: 'article/version-preview.latte', params: [
            'title' => '履歴プレビュー',
            'articleVersion' => $articleVersion->toArray($this->markdown->toSafeHtml(markdown: $articleVersion->bodyMarkdown)),
        ]));
    }
}
