<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Setting\Edit;

use App\Cms\Application\Setting\Edit\Input;
use App\Cms\Application\Setting\Edit\Interactor;
use App\Cms\Application\Media\List;
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
        private List\Interactor $media,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $settings = $this->edit->execute(input: new Input())->settings;
        $media = $this->media->execute(input: new List\Input())->rows;

        return new Response(body: $this->renderer->render(template: 'setting/edit.latte', params: [
            'title' => 'サイト設定',
            'settings' => [
                'site_name' => $settings->siteName,
                'site_description' => $settings->siteDescription,
                'contact_email' => $settings->contactEmail,
                'front_theme' => $settings->frontTheme,
                'hero_image' => $settings->heroImage,
                'hero_label' => $settings->heroLabel,
                'hero_title' => $settings->heroTitle,
                'hero_description' => $settings->heroDescription,
                'search_enabled' => $settings->searchEnabled ? '1' : '0',
            ],
            'media' => array_values(array_filter(
                array_map(fn ($item): array => $item->toArray(), $media),
                static fn (array $item): bool => ($item['is_image'] ?? false) === true,
            )),
            'flash' => $this->session->pullFlash(key: 'success'),
        ]));
    }
}
