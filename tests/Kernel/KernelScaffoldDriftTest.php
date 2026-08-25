<?php

declare(strict_types=1);

namespace Tests\Kernel;

use PHPUnit\Framework\TestCase;

final class KernelScaffoldDriftTest extends TestCase
{
    public function testGeneratedKernelApplicationDoesNotDriftFromCurrentKernel(): void
    {
        $projectRoot = dirname(__DIR__, 2);

        self::assertSame(
            $this->read(path: $projectRoot . '/apps/kernel/src/Application.php'),
            $this->read(path: $projectRoot . '/packages/framework/templates/kernel/src/Application.php'),
        );
        self::assertSame(
            $this->read(path: $projectRoot . '/apps/kernel/src/ApplicationDefinition.php'),
            $this->read(path: $projectRoot . '/packages/framework/templates/kernel/src/ApplicationDefinition.php'),
        );
        self::assertSame(
            $this->read(path: $projectRoot . '/apps/kernel/src/Environment.php'),
            $this->read(path: $projectRoot . '/packages/framework/templates/kernel/src/Environment.php'),
        );
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertIsString($contents);

        return $contents;
    }
}
