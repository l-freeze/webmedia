<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation\Http\Controller\Sitemap;

use App\Framework\Http\Request;
use App\Framework\Http\Response;
use App\FrontBusiness\Application\Sitemap\Build\Input;
use App\FrontBusiness\Application\Sitemap\Build\Interactor;
use App\FrontBusiness\Presentation\Sitemap\SitemapXml;

final readonly class Controller
{
    public function __construct(private Interactor $interactor, private SitemapXml $sitemapXml)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $output = $this->interactor->execute(Input::create());

        return new Response(
            body: $this->sitemapXml->render(entries: $output->entries, fallbackBaseUrl: $this->fallbackBaseUrl($request)),
            headers: ['Content-Type' => 'application/xml; charset=utf-8'],
        );
    }

    private function fallbackBaseUrl(Request $request): string
    {
        $host = $request->server['HTTP_HOST'] ?? '';
        if ($host === '' || str_contains($host, "\r") || str_contains($host, "\n")) {
            return '';
        }

        return ($request->isHttps() ? 'https://' : 'http://') . $host;
    }
}
