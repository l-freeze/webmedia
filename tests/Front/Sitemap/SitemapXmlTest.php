<?php

declare(strict_types=1);

namespace Tests\Front\Sitemap;

use App\Front\Application\Sitemap\Value\SitemapEntry;
use App\Front\Presentation\Seo\CanonicalUrl;
use App\Front\Presentation\Sitemap\SitemapXml;
use PHPUnit\Framework\TestCase;

final class SitemapXmlTest extends TestCase
{
    public function testRendersXmlWithAbsoluteUrls(): void
    {
        $xml = new SitemapXml(canonicalUrl: new CanonicalUrl(baseUrl: 'https://example.com'));

        $body = $xml->render(entries: [
            new SitemapEntry(path: '/', lastmod: '2026-07-03'),
            new SitemapEntry(path: '/articles/php?utm_source=ignore', lastmod: '2026-07-02'),
        ]);

        self::assertStringContainsString('<loc>https://example.com/</loc>', $body);
        self::assertStringContainsString('<lastmod>2026-07-03</lastmod>', $body);
        self::assertStringContainsString('<loc>https://example.com/articles/php</loc>', $body);
        self::assertStringNotContainsString('utm_source', $body);
    }

    public function testSkipsUrlsWhenBaseUrlIsEmpty(): void
    {
        $xml = new SitemapXml(canonicalUrl: new CanonicalUrl(baseUrl: ''));

        self::assertSame(
            "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n</urlset>\n",
            $xml->render(entries: [new SitemapEntry(path: '/')]),
        );
    }

    public function testUsesFallbackBaseUrlWhenConfiguredBaseUrlIsEmpty(): void
    {
        $xml = new SitemapXml(canonicalUrl: new CanonicalUrl(baseUrl: ''));

        self::assertStringContainsString(
            '<loc>https://media.example.test/</loc>',
            $xml->render(entries: [new SitemapEntry(path: '/')], fallbackBaseUrl: 'https://media.example.test'),
        );
    }
}
