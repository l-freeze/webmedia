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
            cacheDirectory: $this->temporaryDirectory(),
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
            cacheDirectory: $this->temporaryDirectory(),
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
            cacheDirectory: $this->temporaryDirectory(),
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
            cacheDirectory: $this->temporaryDirectory(),
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
            cacheDirectory: $this->temporaryDirectory(),
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

    private function temporaryDirectory(): string
    {
        $dir = sys_get_temp_dir() . '/webmedia-latte-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $this->tempDirs[] = $dir;

        return $dir;
    }
}
