<?php

declare(strict_types=1);

namespace Tests\Front\Presentation\Seo;

use App\Front\Presentation\Seo\CanonicalUrl;
use PHPUnit\Framework\TestCase;

final class CanonicalUrlTest extends TestCase
{
    public function testBuildsArticleCanonicalUrl(): void
    {
        $canonicalUrl = new CanonicalUrl(baseUrl: 'https://example.com/');

        self::assertSame(
            'https://example.com/articles/php-architecture',
            $canonicalUrl->article(slug: 'php-architecture'),
        );
    }

    public function testReturnsNullWhenBaseUrlIsEmpty(): void
    {
        $canonicalUrl = new CanonicalUrl(baseUrl: '');

        self::assertNull($canonicalUrl->page(slug: 'privacy'));
    }

    public function testNormalizesSlashes(): void
    {
        $canonicalUrl = new CanonicalUrl(baseUrl: 'https://example.com///');

        self::assertSame(
            'https://example.com/categories/php',
            $canonicalUrl->category(slug: '/php'),
        );
    }

    public function testDropsQueryStringFromPath(): void
    {
        $canonicalUrl = new CanonicalUrl(baseUrl: 'https://example.com');

        self::assertSame(
            'https://example.com/tags/php',
            $canonicalUrl->fromPath(path: '/tags/php?utm_source=mail'),
        );
    }

    public function testReturnsNullWhenBaseUrlIsInvalid(): void
    {
        $canonicalUrl = new CanonicalUrl(baseUrl: 'example.com');

        self::assertNull($canonicalUrl->fromPath(path: '/tags/php'));
    }

    public function testReturnsNullWhenPathIsAbsoluteUri(): void
    {
        $canonicalUrl = new CanonicalUrl(baseUrl: 'https://example.com');

        self::assertNull($canonicalUrl->fromPath(path: 'https://other.example/tags/php'));
    }

    public function testReturnsNullWhenPathIsMalformed(): void
    {
        $canonicalUrl = new CanonicalUrl(baseUrl: 'https://example.com');

        self::assertNull($canonicalUrl->fromPath(path: '%'));
    }
}
