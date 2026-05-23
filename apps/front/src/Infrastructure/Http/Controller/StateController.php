<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Http\Controller;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\Renderer;

final readonly class StateController
{
    public function __construct(private Renderer $renderer)
    {
    }

    /** @param array<string, string> $vars */
    public function loading(Request $request, array $vars = []): Response
    {
        return $this->simple('読み込み中', '記事を読み込んでいます。');
    }

    /** @param array<string, string> $vars */
    public function noArticles(Request $request, array $vars = []): Response
    {
        return $this->simple('記事がありません', '公開中の記事がまだありません。');
    }

    /** @param array<string, string> $vars */
    public function menu(Request $request, array $vars = []): Response
    {
        return $this->simple('メニュー', 'カテゴリ、タグ、固定ページ、問い合わせへの導線をまとめています。');
    }

    public function notFound(Request $request): Response
    {
        return $this->simple('ページが見つかりません', 'URLを確認するか、トップページから記事を探してください。', 404);
    }

    private function simple(string $heading, string $message, int $status = 200): Response
    {
        return new Response($this->renderer->render('state/simple.latte', [
            'title' => $heading,
            'siteName' => 'WEB Media',
            'heading' => $heading,
            'message' => $message,
        ]), $status);
    }
}
