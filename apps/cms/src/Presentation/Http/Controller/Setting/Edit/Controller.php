<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Setting\Edit;

use App\Cms\Application\Setting\Edit\Input;
use App\Cms\Application\Setting\Edit\Interactor;
use App\Cms\Application\Media\List;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Session;
use Pera\Framework\View\ITemplateRenderer;

final readonly class Controller
{
    public function __construct(
        private ITemplateRenderer $renderer,
        private Session $session,
        private Interactor $edit,
        private List\Interactor $media,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $settings = $this->edit->execute(Input::create())->settings;
        $media = $this->media->execute(List\Input::create())->rows;
        $imageMedia = [];
        foreach ($media as $item) {
            $mediaItem = $item->toArray();
            if (($mediaItem['is_image'] ?? false) === true) {
                $imageMedia[] = $mediaItem;
            }
        }

        return new Response(body: $this->renderer->render(template: 'setting/edit.latte', params: [
            'title' => 'サイト設定',
            'settings' => [
                'site_name' => $settings->siteName,
                'site_description' => $settings->siteDescription,
                'contact_email' => $settings->contactEmail,
                'front_theme' => $settings->frontTheme,
                'hero_image' => $settings->heroImage,
                'hero_title' => $settings->heroTitle,
                'hero_description' => $settings->heroDescription,
                'search_enabled' => $settings->searchEnabled ? '1' : '0',
            ],
            'media' => $imageMedia,
            'flash' => $this->session->pullFlash(key: 'success'),
        ]));
    }
}
