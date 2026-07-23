<?php

declare(strict_types=1);

namespace Tests\Front\Application;

use DirectoryIterator;
use PHPUnit\Framework\TestCase;

final class UseCaseStructureTest extends TestCase
{
    public function testSecondLevelDirectoriesAreUseCases(): void
    {
        $violations = [];
        foreach ($this->useCaseDirectories() as $directory) {
            foreach (['Input.php', 'Interactor.php', 'Output.php'] as $requiredFile) {
                if (!is_file($directory . '/' . $requiredFile)) {
                    $violations[] = $this->relativePath(path: $directory) . ' does not have ' . $requiredFile;
                }
            }
        }

        self::assertSame([], $violations);
    }

    /** @return list<string> */
    private function useCaseDirectories(): array
    {
        $directories = [];
        $root = self::root() . '/apps/front/src/Application';
        foreach (new DirectoryIterator($root) as $feature) {
            if (!$feature->isDir() || $feature->isDot()) {
                continue;
            }

            foreach (new DirectoryIterator($feature->getPathname()) as $action) {
                if ($action->isDir() && !$action->isDot()) {
                    $directories[] = $action->getPathname();
                }
            }
        }

        sort($directories);
        return $directories;
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
