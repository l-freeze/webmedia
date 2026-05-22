<?php

declare(strict_types=1);

namespace App\Cms\Setting\Infrastructure\Http\Controller;

use App\Cms\Infrastructure\Http\AuthGuard;
use App\Cms\Setting\Query\GetSettings\SettingRepository;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\Renderer;

final readonly class SettingController
{
    use AuthGuard;

    public function __construct(private Renderer $renderer, private Session $session, private SettingRepository $settings)
    {
    }

    /** @param array<string, string> $vars */
    public function edit(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $settings = $this->settings->all();
        return new Response($this->renderer->render('simple-list.latte', [
            'title' => 'サイト設定',
            'heading' => 'サイト設定',
            'columns' => ['項目', '値'],
            'rows' => [['サイト名', $settings['site_name'] ?? ''], ['説明', $settings['site_description'] ?? ''], ['通知先', $settings['contact_email'] ?? '']],
            'form' => [['label' => 'サイト名', 'name' => 'site_name'], ['label' => '説明', 'name' => 'site_description'], ['label' => '通知先メール', 'name' => 'contact_email']],
        ]));
    }

    /** @param array<string, string> $vars */
    public function update(Request $request, array $vars = []): Response
    {
        if ($response = $this->requireAuth($this->session)) {
            return $response;
        }
        $this->settings->save($request->body);
        return Response::redirect('/admin/settings');
    }
}
