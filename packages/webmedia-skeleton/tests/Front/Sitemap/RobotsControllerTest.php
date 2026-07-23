<?php

declare(strict_types=1);

namespace Tests\Front\Sitemap;

use Pera\Framework\Http\Request;
use App\Front\Presentation\Http\Controller\Robots\Controller;
use App\Front\Presentation\Seo\CanonicalUrl;
use PHPUnit\Framework\TestCase;

final class RobotsControllerTest extends TestCase
{
    public function testRendersAdminDisallowAndSitemap(): void
    {
        $controller = new Controller(canonicalUrl: new CanonicalUrl(baseUrl: 'https://example.com'));

        $response = $controller(new Request(method: 'GET', path: '/robots.txt', query: [], body: [], server: []));

        self::assertSame('text/plain; charset=utf-8', $response->headers['Content-Type']);
        self::assertStringContainsString("Disallow: /admin\n", $response->body);
        self::assertStringContainsString("Disallow: /admin/\n", $response->body);
        self::assertStringContainsString("Sitemap: https://example.com/sitemap.xml\n", $response->body);
    }

    public function testUsesRequestHostWhenBaseUrlIsEmpty(): void
    {
        $controller = new Controller(canonicalUrl: new CanonicalUrl(baseUrl: ''));

        $response = $controller(new Request(method: 'GET', path: '/robots.txt', query: [], body: [], server: [
            'HTTP_HOST' => 'media.example.test',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ]));

        self::assertStringContainsString("Sitemap: https://media.example.test/sitemap.xml\n", $response->body);
    }
}
