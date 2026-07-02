<?php

declare(strict_types=1);

namespace App\Front\Presentation\Sitemap;

use App\Front\Application\Sitemap\Value\SitemapEntry;
use App\Front\Presentation\Seo\CanonicalUrl;

final readonly class SitemapXml
{
    public function __construct(private CanonicalUrl $canonicalUrl)
    {
    }

    /**
     * @param array<int, SitemapEntry> $entries
     */
    public function render(array $entries, string $fallbackBaseUrl = ''): string
    {
        $urls = [];
        foreach ($entries as $entry) {
            $loc = $this->canonicalUrl->fromPathWithFallbackBase(path: $entry->path, fallbackBaseUrl: $fallbackBaseUrl);
            if ($loc === null) {
                continue;
            }

            $urls[] = $this->url(loc: $loc, lastmod: $entry->lastmod);
        }

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
            . implode('', $urls)
            . "</urlset>\n";
    }

    private function url(string $loc, ?string $lastmod): string
    {
        $xml = "  <url>\n";
        $xml .= '    <loc>' . htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</loc>\n";
        if ($lastmod !== null && $lastmod !== '') {
            $xml .= '    <lastmod>' . htmlspecialchars($lastmod, ENT_XML1 | ENT_COMPAT, 'UTF-8') . "</lastmod>\n";
        }
        $xml .= "  </url>\n";

        return $xml;
    }
}
