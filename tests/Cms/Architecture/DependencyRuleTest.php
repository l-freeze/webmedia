<?php

declare(strict_types=1);

namespace Tests\Cms\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DependencyRuleTest extends TestCase
{
    public function testCmsDoesNotDependOnFront(): void
    {
        $this->assertNoForbiddenReferences(
            root: self::root() . '/apps/cms/src',
            forbiddenNeedles: ['App\\Front\\', 'front.layout_globals'],
        );
    }

    public function testApplicationDoesNotDependOnInfrastructure(): void
    {
        $this->assertNoForbiddenReferences(
            root: self::root() . '/apps/cms/src/Application',
            forbiddenNeedles: ['\\Infrastructure\\'],
        );
    }

    public function testDomainDoesNotDependOnApplicationOrInfrastructure(): void
    {
        $this->assertNoForbiddenReferences(
            root: self::root() . '/apps/cms/src/Domain',
            forbiddenNeedles: ['\\Application\\', '\\Infrastructure\\'],
        );
    }

    /** @param list<string> $forbiddenNeedles */
    private function assertNoForbiddenReferences(string $root, array $forbiddenNeedles): void
    {
        $violations = [];
        foreach ($this->phpFiles(root: $root) as $file) {
            $lines = file($file->getPathname());
            self::assertIsArray($lines);

            foreach ($lines as $lineNumber => $line) {
                foreach ($forbiddenNeedles as $needle) {
                    if (str_contains($line, $needle)) {
                        $violations[] = sprintf(
                            '%s:%d references %s',
                            $this->relativePath(path: $file->getPathname()),
                            $lineNumber + 1,
                            $needle,
                        );
                    }
                }
            }
        }

        self::assertSame([], $violations);
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $root): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            iterator: new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file;
            }
        }

        return $files;
    }

    private static function root(): string
    {
        return dirname(__DIR__, 3);
    }

    private function relativePath(string $path): string
    {
        return substr($path, strlen(self::root()) + 1);
    }
}
