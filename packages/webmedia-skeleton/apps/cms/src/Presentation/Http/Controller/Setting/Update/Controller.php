<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Http\Controller\Setting\Update;

use App\Cms\Application\Setting\Save\Input;
use App\Cms\Application\Setting\Save\Interactor;
use App\Cms\Presentation\Audit\AuditReporter;
use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use Pera\Framework\Security\Session;

final readonly class Controller
{
    public function __construct(
        private Session $session,
        private Interactor $save,
        private AuditReporter $audit,
    ) {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $this->save->execute(Input::create(
            siteName: $request->bodyInput('site_name'),
            siteDescription: $request->bodyInput('site_description'),
            contactEmail: $request->bodyInput('contact_email'),
            frontTheme: $request->bodyInput('front_theme'),
            searchEnabled: $request->bodyInput('search_enabled', '1') === '1',
            heroImage: $request->bodyInput('hero_image'),
            heroTitle: $request->bodyInput('hero_title'),
            heroDescription: $request->bodyInput('hero_description'),
        ));
        $this->audit->settingsUpdated($request);

        $this->session->flash(key: 'success', value: '保存しました');

        return Response::redirect('/admin/settings');
    }
}
