<?php

declare(strict_types=1);

namespace Tests\Architecture;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ErrorSuppressionOperatorTest extends TestCase
{
    public function testPhpSourceDoesNotUseErrorSuppressionOperator(): void
    {
        $root = dirname(__DIR__, 2);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
            $root,
            FilesystemIterator::SKIP_DOTS,
        ));
        $violations = [];

        foreach ($files as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = substr($file->getPathname(), strlen($root) + 1);
            if ($this->isExcluded(relativePath: $relativePath)) {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);
            $line = 1;
            foreach (token_get_all($source) as $token) {
                if (is_array($token)) {
                    $line = $token[2] + substr_count($token[1], "\n");
                    continue;
                }

                if ($token === '@') {
                    $violations[] = "{$relativePath}:{$line}";
                }
                $line += substr_count($token, "\n");
            }
        }

        self::assertSame(
            [],
            $violations,
            "PHP error suppression operator is prohibited:\n" . implode("\n", $violations),
        );
    }

    private function isExcluded(string $relativePath): bool
    {
        $path = '/' . $relativePath;
        foreach (['/.git/', '/storage/', '/vendor/'] as $segment) {
            if (str_contains($path, $segment)) {
                return true;
            }
        }

        return false;
    }
}
