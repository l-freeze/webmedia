<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Http\Controller\Setting\Edit;

use App\Cms\Application\Setting\Edit\Input;
use App\Cms\Application\Setting\Edit\Interactor;
use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\Framework\Security\Session;
use App\Framework\View\TemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private TemplateRenderer $renderer,
        private Session $session,
        private Interactor $edit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $settings = $this->edit->execute(input: new Input())->settings;

        return new Response(body: $this->renderer->render(template: 'simple-list.latte', params: [
            'title' => 'サイト設定',
            'heading' => 'サイト設定',
            'columns' => ['項目', '値'],
            'rows' => [
                ['サイト名', $settings->siteName],
                ['説明', $settings->siteDescription],
                ['通知先', $settings->contactEmail],
                ['フロントデザイン', $this->themeLabel(theme: $settings->frontTheme)],
                ['検索機能', $settings->searchEnabled ? '有効' : '無効'],
            ],
            'form' => [
                ['label' => 'サイト名', 'name' => 'site_name'],
                ['label' => '説明', 'name' => 'site_description'],
                ['label' => '通知先メール', 'name' => 'contact_email'],
                [
                    'label' => 'フロントデザイン',
                    'name' => 'front_theme',
                    'type' => 'select',
                    'options' => [
                        'cafe-clean' => '10代向け: カフェクリーン',
                        'tech-edge' => '20代技術営業向け: テックエッジ',
                        'executive-trust' => '30代責任者向け: エグゼクティブ',
                        'dev-docs' => '技術系: デブドックス',
                        'recipe-fresh' => '料理系: レシピフレッシュ',
                        'pop-girly' => '10代女の子向け: ポップガーリー',
                        'mama-soft' => '子持ちママ向け: ママソフト',
                    ],
                ],
                [
                    'label' => '検索機能',
                    'name' => 'search_enabled',
                    'type' => 'select',
                    'options' => [
                        '1' => '有効',
                        '0' => '無効',
                    ],
                ],
            ],
            'formValues' => [
                'site_name' => $settings->siteName,
                'site_description' => $settings->siteDescription,
                'contact_email' => $settings->contactEmail,
                'front_theme' => $settings->frontTheme,
                'search_enabled' => $settings->searchEnabled ? '1' : '0',
            ],
            'flash' => $this->session->pullFlash(key: 'success'),
        ]));
    }

    private function themeLabel(string $theme): string
    {
        return match ($theme) {
            'tech-edge' => '20代技術営業向け: テックエッジ',
            'executive-trust' => '30代責任者向け: エグゼクティブ',
            'dev-docs' => '技術系: デブドックス',
            'recipe-fresh' => '料理系: レシピフレッシュ',
            'pop-girly' => '10代女の子向け: ポップガーリー',
            'mama-soft' => '子持ちママ向け: ママソフト',
            default => '10代向け: カフェクリーン',
        };
    }
}
