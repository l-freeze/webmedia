<?php

declare(strict_types=1);

namespace Tests\Front\Templates;

use App\Shared\View\Infrastructure\LatteTemplateRenderer;
use PHPUnit\Framework\TestCase;

final class DefaultLayoutCanonicalTest extends TestCase
{
    public function testRendersCanonicalLinkWhenCanonicalUrlIsProvided(): void
    {
        $renderer = new LatteTemplateRenderer(
            templateDirectory: dirname(__DIR__, 3) . '/apps/front/templates',
        );

        $output = $renderer->render(template: 'state/simple.latte', params: [
            ...$this->baseParams(),
            'canonicalUrl' => 'https://example.com/articles/php-architecture',
        ]);

        self::assertStringContainsString(
            '<link rel="canonical" href="https://example.com/articles/php-architecture">',
            $output,
        );
    }

    public function testDoesNotRenderCanonicalLinkWhenCanonicalUrlIsMissing(): void
    {
        $renderer = new LatteTemplateRenderer(
            templateDirectory: dirname(__DIR__, 3) . '/apps/front/templates',
        );

        $output = $renderer->render(template: 'state/simple.latte', params: $this->baseParams());

        self::assertStringNotContainsString('rel="canonical"', $output);
    }

    /** @return array<string, mixed> */
    private function baseParams(): array
    {
        return [
            'title' => '表示テスト',
            'siteName' => 'WEB Media',
            'description' => '',
            'frontTheme' => 'cafe-clean',
            'publishedPages' => [],
            'publishedCategories' => [],
            'searchEnabled' => false,
            'heading' => '表示テスト',
            'message' => '本文',
        ];
    }

}
