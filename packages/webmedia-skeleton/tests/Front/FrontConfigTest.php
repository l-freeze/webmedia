<?php

declare(strict_types=1);

namespace Tests\Front;

use PHPUnit\Framework\TestCase;

final class FrontConfigTest extends TestCase
{
    public function testFrontTemplatesDoNotContainInlineScript(): void
    {
        $templateDirectory = dirname(__DIR__, 2) . '/apps/front/templates';
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templateDirectory));

        foreach ($files as $file) {
            if (!$file instanceof \SplFileInfo || $file->getExtension() !== 'latte') {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            self::assertDoesNotMatchRegularExpression('/<script\b(?![^>]*\bsrc=)/i', $contents, $file->getPathname());
            self::assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $contents, $file->getPathname());
        }
    }

    public function testFrontContainerDefinesCspWithoutInlineScriptSource(): void
    {
        $contents = (string) file_get_contents(dirname(__DIR__, 2) . '/apps/front/config/container.php');

        self::assertStringContainsString("script-src 'self' https://unpkg.com", $contents);
        self::assertStringNotContainsString("script-src 'self' 'unsafe-inline'", $contents);
        self::assertStringNotContainsString('script-src \'self\' https://unpkg.com https://cdn.jsdelivr.net', $contents);
    }
}
