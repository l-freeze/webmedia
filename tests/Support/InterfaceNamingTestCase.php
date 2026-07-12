<?php

declare(strict_types=1);

namespace Tests\Support;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

abstract class InterfaceNamingTestCase extends TestCase
{
    final protected function assertInterfacesUseIPrefix(string $sourceDirectory): void
    {
        $violations = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($sourceDirectory));
        foreach ($iterator as $file) {
            if (!$file instanceof SplFileInfo || !$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $source = file_get_contents($file->getPathname());
            self::assertIsString($source);
            $interfaceName = $this->interfaceName($source);
            if ($interfaceName === null) {
                continue;
            }

            if (!str_starts_with($interfaceName, 'I')) {
                $violations[] = $file->getPathname() . " defines {$interfaceName} without an I prefix";
            }
            if ($file->getBasename('.php') !== $interfaceName) {
                $violations[] = $file->getPathname() . " does not match interface {$interfaceName}";
            }
        }

        self::assertSame([], $violations);
    }

    private function interfaceName(string $source): ?string
    {
        $waitingForName = false;
        foreach (token_get_all($source) as $token) {
            if (!is_array($token)) {
                continue;
            }
            if ($token[0] === T_INTERFACE) {
                $waitingForName = true;
                continue;
            }
            if ($waitingForName && $token[0] === T_STRING) {
                return $token[1];
            }
        }

        return null;
    }
}
