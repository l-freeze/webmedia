<?php

declare(strict_types=1);

namespace Tests\Front\Templates;

use App\Shared\View\Infrastructure\LatteTemplateRenderer;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DefaultLayoutCanonicalTest extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tempDirs) as $dir) {
            $this->removeDirectory(directory: $dir);
        }
    }

    public function testRendersCanonicalLinkWhenCanonicalUrlIsProvided(): void
    {
        $renderer = new LatteTemplateRenderer(
            templateDirectory: dirname(__DIR__, 3) . '/apps/front/templates',
            cacheDirectory: $this->temporaryDirectory(),
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
            cacheDirectory: $this->temporaryDirectory(),
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

    private function temporaryDirectory(): string
    {
        $dir = sys_get_temp_dir() . '/webmedia-front-template-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $this->tempDirs[] = $dir;

        return $dir;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo) {
                continue;
            }

            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }

        rmdir($directory);
    }
}
