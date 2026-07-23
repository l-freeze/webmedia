<?php

declare(strict_types=1);

namespace App\Front\Presentation\Http\Controller\Robots;

use Pera\Framework\Http\Request;
use Pera\Framework\Http\Response;
use App\Front\Presentation\Seo\CanonicalUrl;

final readonly class Controller
{
    public function __construct(private CanonicalUrl $canonicalUrl)
    {
    }

    /** @param array<string, string> $vars */
    public function __invoke(Request $request, array $vars = []): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /admin/',
        ];

        $sitemapUrl = $this->canonicalUrl->fromPathWithFallbackBase(path: '/sitemap.xml', fallbackBaseUrl: $this->fallbackBaseUrl($request));
        if ($sitemapUrl !== null) {
            $lines[] = '';
            $lines[] = 'Sitemap: ' . $sitemapUrl;
        }

        return new Response(
            body: implode("\n", $lines) . "\n",
            headers: ['Content-Type' => 'text/plain; charset=utf-8'],
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
