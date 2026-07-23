<?php

declare(strict_types=1);

namespace Tests\Shared\View;

use App\Shared\View\Infrastructure\LatteTemplateRenderer;
use PHPUnit\Framework\TestCase;

final class LatteTemplateRendererTest extends TestCase
{
    /** @var list<string> */
    private array $tempDirs = [];

    protected function tearDown(): void
    {
        foreach (array_reverse($this->tempDirs) as $dir) {
            foreach (glob($dir . '/*') ?: [] as $file) {
                is_dir($file) ? rmdir($file) : unlink($file);
            }
            rmdir($dir);
        }
    }

    public function testRendersTemplateWithParams(): void
    {
        $templateDir = $this->temporaryDirectory();
        file_put_contents($templateDir . '/test.latte', '<p>{$message}</p>');

        $renderer = new LatteTemplateRenderer(
            templateDirectory: $templateDir,
        );

        $output = $renderer->render(template: 'test.latte', params: ['message' => 'hello']);

        self::assertStringContainsString('<p>hello</p>', $output);
    }

    public function testGlobalProviderIsApplied(): void
    {
        $templateDir = $this->temporaryDirectory();
        file_put_contents($templateDir . '/test.latte', '<p>{$global}</p>');

        $renderer = new LatteTemplateRenderer(
            templateDirectory: $templateDir,
        );
        $renderer->addGlobalProvider(provider: fn() => ['global' => 'world']);

        $output = $renderer->render(template: 'test.latte');

        self::assertStringContainsString('<p>world</p>', $output);
    }

    public function testLocalParamsOverrideGlobalProvider(): void
    {
        $templateDir = $this->temporaryDirectory();
        file_put_contents($templateDir . '/test.latte', '<p>{$value}</p>');

        $renderer = new LatteTemplateRenderer(
            templateDirectory: $templateDir,
        );
        $renderer->addGlobalProvider(provider: fn() => ['value' => 'global']);

        $output = $renderer->render(template: 'test.latte', params: ['value' => 'local']);

        self::assertStringContainsString('<p>local</p>', $output);
    }

    public function testParamsAreEvaluatedOnEveryRender(): void
    {
        $templateDir = $this->temporaryDirectory();
        file_put_contents($templateDir . '/test.latte', '<p>{$message}</p>');

        $renderer = new LatteTemplateRenderer(
            templateDirectory: $templateDir,
        );

        $first = $renderer->render(template: 'test.latte', params: ['message' => 'first']);
        $second = $renderer->render(template: 'test.latte', params: ['message' => 'second']);

        self::assertStringContainsString('<p>first</p>', $first);
        self::assertStringContainsString('<p>second</p>', $second);
    }

    public function testGlobalProvidersAreEvaluatedOnEveryRender(): void
    {
        $templateDir = $this->temporaryDirectory();
        file_put_contents($templateDir . '/test.latte', '<p>{$value}</p>');

        $value = 'first';
        $renderer = new LatteTemplateRenderer(
            templateDirectory: $templateDir,
        );
        $renderer->addGlobalProvider(provider: function () use (&$value): array {
            return ['value' => $value];
        });

        $first = $renderer->render(template: 'test.latte');
        $value = 'second';
        $second = $renderer->render(template: 'test.latte');

        self::assertStringContainsString('<p>first</p>', $first);
        self::assertStringContainsString('<p>second</p>', $second);
    }

    public function testCompiledCacheIsDisabledByDefault(): void
    {
        $templateDir = $this->temporaryDirectory();
        file_put_contents($templateDir . '/test.latte', '<p>cached?</p>');

        $renderer = new LatteTemplateRenderer(templateDirectory: $templateDir);

        $renderer->render(template: 'test.latte');

        self::assertNull($this->cacheDirectoryOf(renderer: $renderer));
    }

    public function testCompiledCacheCanBeEnabledExplicitly(): void
    {
        $templateDir = $this->temporaryDirectory();
        $cacheDir = $this->temporaryDirectory();
        file_put_contents($templateDir . '/test.latte', '<p>cached</p>');

        $renderer = new LatteTemplateRenderer(
            templateDirectory: $templateDir,
            cacheDirectory: $cacheDir,
        );

        $renderer->render(template: 'test.latte');

        self::assertSame($cacheDir, $this->cacheDirectoryOf(renderer: $renderer));
        self::assertNotSame([], glob($cacheDir . '/*'));
    }

    private function cacheDirectoryOf(LatteTemplateRenderer $renderer): ?string
    {
        $rendererReflection = new \ReflectionObject($renderer);
        $engineProperty = $rendererReflection->getProperty('engine');
        $engine = $engineProperty->getValue($renderer);
        if (!is_object($engine)) {
            throw new \RuntimeException('Latte engine is not available.');
        }

        $engineReflection = new \ReflectionObject($engine);
        $cacheProperty = $engineReflection->getProperty('cache');
        $cache = $cacheProperty->getValue($engine);
        if (!is_object($cache)) {
            throw new \RuntimeException('Latte cache is not available.');
        }

        $cacheReflection = new \ReflectionObject($cache);
        $directoryProperty = $cacheReflection->getProperty('directory');
        $directory = $directoryProperty->getValue($cache);

        return is_string($directory) ? $directory : null;
    }

    private function temporaryDirectory(): string
    {
        $dir = sys_get_temp_dir() . '/webmedia-latte-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $this->tempDirs[] = $dir;

        return $dir;
    }
}
