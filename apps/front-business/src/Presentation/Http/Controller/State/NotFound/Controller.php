<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\State\NotFound;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(private ITemplateRenderer $renderer)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        return new Response(body: $this->renderer->render(template: 'state/simple.latte', params: [
            'title' => 'ページが見つかりません',
            'heading' => 'ページが見つかりません',
            'message' => 'URLを確認するか、トップページから記事を探してください。',
        ]), status: Response::HTTP_NOT_FOUND);
    }
}
