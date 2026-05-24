<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller;

use App\Cms\Infrastructure\Http\AuthGuard;
use App\Cms\Application\Setting\Edit\Input as EditInput;
use App\Cms\Application\Setting\Edit\Interactor as EditInteractor;
use App\Cms\Application\Setting\Save\Input as SaveInput;
use App\Cms\Application\Setting\Save\Interactor as SaveInteractor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class SettingController
{
    use AuthGuard;

    public function __construct(
        private Renderer $renderer,
        private Session $session,
        private EditInteractor $edit,
        private SaveInteractor $save,
    ) {
    }

    /** @param array<string, string> $vars */
    public function edit(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $settings = $this->edit->execute(new EditInput())->settings;
        return new Response($this->renderer->render('simple-list.latte', [
            'title' => 'サイト設定',
            'heading' => 'サイト設定',
            'columns' => ['項目', '値'],
            'rows' => [['サイト名', $settings->siteName], ['説明', $settings->siteDescription], ['通知先', $settings->contactEmail]],
            'form' => [['label' => 'サイト名', 'name' => 'site_name'], ['label' => '説明', 'name' => 'site_description'], ['label' => '通知先メール', 'name' => 'contact_email']],
            'flash' => $this->session->pullFlash('success'),
        ]));
    }

    /** @param array<string, string> $vars */
    public function update(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $this->save->execute(new SaveInput($request->body));
        $this->session->flash('success', '保存しました');
        return Response::redirect('/admin/settings');
    }
}
