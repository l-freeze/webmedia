<?php

declare(strict_types=1);

namespace Tests\Shared\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DependencyRuleTest extends TestCase
{
    public function testSharedDoesNotDependOnApplications(): void
    {
        $violations = [];
        $iterator = new RecursiveIteratorIterator(
            iterator: new RecursiveDirectoryIterator(
                self::root() . '/apps/shared/src',
                RecursiveDirectoryIterator::SKIP_DOTS,
            ),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $lines = file($file->getPathname());
            self::assertIsArray($lines);
            foreach ($lines as $lineNumber => $line) {
                foreach (['App\\Cms\\', 'App\\Front\\'] as $forbiddenNeedle) {
                    if (str_contains($line, $forbiddenNeedle)) {
                        $violations[] = sprintf(
                            '%s:%d references %s',
                            substr($file->getPathname(), strlen(self::root()) + 1),
                            $lineNumber + 1,
                            $forbiddenNeedle,
                        );
                    }
                }
            }
        }

        self::assertSame([], $violations);
    }

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }
}
