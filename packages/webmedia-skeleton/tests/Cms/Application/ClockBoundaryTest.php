<?php

declare(strict_types=1);

namespace Tests\Cms\Application;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ClockBoundaryTest extends TestCase
{
    public function testApplicationDoesNotCreateCurrentTimeDirectly(): void
    {
        $violations = [];
        foreach ($this->applicationPhpFiles() as $file) {
            $source = file_get_contents($file);
            self::assertIsString($source);
            if (preg_match('/new\s+\\\\?DateTimeImmutable\s*\(\s*\)/', $source) === 1) {
                $violations[] = $this->relativePath($file) . ' creates the current time directly';
            }
        }

        self::assertSame([], $violations);
    }

    /** @return list<string> */
    private function applicationPhpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(self::root() . '/apps/cms/src/Application'));
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        sort($files);
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
