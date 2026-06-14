<?php

declare(strict_types=1);

namespace Tests\Architecture;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class DependencyRuleTest extends TestCase
{
    public function testFrontDoesNotDependOnCms(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [self::root() . '/apps/front/src'],
            forbiddenNeedles: ['App\\Cms\\'],
        );
    }

    public function testCmsDoesNotDependOnFront(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [self::root() . '/apps/cms/src'],
            forbiddenNeedles: ['App\\Front\\'],
        );
    }

    public function testApplicationDoesNotDependOnInfrastructure(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [
                self::root() . '/apps/front/src/Application',
                self::root() . '/apps/cms/src/Application',
            ],
            forbiddenNeedles: ['\\Infrastructure\\'],
        );
    }

    public function testDomainDoesNotDependOnApplicationOrInfrastructure(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [
                self::root() . '/apps/front/src/Domain',
                self::root() . '/apps/cms/src/Domain',
            ],
            forbiddenNeedles: ['\\Application\\', '\\Infrastructure\\'],
        );
    }

    public function testSharedDoesNotDependOnApps(): void
    {
        $this->assertNoForbiddenReferences(
            roots: [self::root() . '/apps/shared/src'],
            forbiddenNeedles: ['App\\Cms\\', 'App\\Front\\'],
        );
    }

    /**
     * @param list<string> $roots
     * @param list<string> $forbiddenNeedles
     */
    private function assertNoForbiddenReferences(array $roots, array $forbiddenNeedles): void
    {
        $violations = [];
        foreach ($roots as $root) {
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
        }

        self::assertSame([], $violations);
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(string $root): array
    {
        if (!is_dir($root)) {
            return [];
        }

        $files = [];
        $iterator = new RecursiveIteratorIterator(
            iterator: new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $files[] = $file;
        }

        usort($files, static fn (SplFileInfo $a, SplFileInfo $b): int => $a->getPathname() <=> $b->getPathname());

        return $files;
    }

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function relativePath(string $path): string
    {
        return ltrim(str_replace(self::root(), '', $path), '/');
    }
}
