<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller;

use App\Cms\Infrastructure\Http\AuthGuard;
use App\Cms\Application\Media\List\Input;
use App\Cms\Application\Media\List\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class MediaController
{
    use AuthGuard;

    public function __construct(private Renderer $renderer, private Session $session, private Interactor $interactor)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $output = $this->interactor->execute(new Input());

        return new Response($this->renderer->render('simple-list.latte', [
            'title' => 'メディア管理',
            'heading' => 'メディア管理',
            'columns' => ['ファイル名', '形式', 'サイズ', 'アップロード日'],
            'rows' => array_map(fn ($row): array => $row->toArray(), $output->rows),
        ]));
    }
}
