<?php

declare(strict_types=1);

namespace Tests\Cms\Domain;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ArticlePermissionBoundaryTest extends TestCase
{
    public function testPermissionValuesAreIssuedOnlyByArticlePolicy(): void
    {
        $violations = [];
        $allowedFile = self::root() . '/apps/cms/src/Domain/Policy/ArticlePolicy.php';
        $permissionClasses = [
            'ArticleCreatePermission',
            'ArticleUpdatePermission',
            'ArticlePublishPermission',
            'ArticleDeletePermission',
            'ArticleRestorePermission',
        ];

        foreach ($this->phpFiles() as $file) {
            if ($file->getPathname() === $allowedFile) {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            self::assertIsString($contents);
            foreach ($permissionClasses as $permissionClass) {
                if (str_contains($contents, 'new ' . $permissionClass)) {
                    $violations[] = sprintf(
                        '%s creates %s outside Domain/Policy/ArticlePolicy',
                        substr($file->getPathname(), strlen(self::root()) + 1),
                        $permissionClass,
                    );
                }
            }
        }

        self::assertSame([], $violations);
    }

    /** @return list<SplFileInfo> */
    private function phpFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            iterator: new RecursiveDirectoryIterator(
                self::root() . '/apps/cms/src',
                RecursiveDirectoryIterator::SKIP_DOTS,
            ),
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
}
