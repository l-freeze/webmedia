<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

final class FrameworkPackageConsumptionTest extends TestCase
{
    public function testRootConsumesFrameworkThroughPathPackageWithoutDuplicateAutoload(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = $this->manifest(path: $root . '/composer.json');

        $requirements = $manifest['require'] ?? null;
        self::assertIsArray($requirements);
        self::assertSame('dev-main', $requirements['pera/framework'] ?? null);

        $autoload = $manifest['autoload'] ?? null;
        self::assertIsArray($autoload);
        $psr4 = $autoload['psr-4'] ?? null;
        self::assertIsArray($psr4);
        self::assertArrayNotHasKey('Pera\\Framework\\', $psr4);

        $repositories = $manifest['repositories'] ?? null;
        self::assertIsArray($repositories);
        $frameworkRepository = $repositories[0] ?? null;
        self::assertIsArray($frameworkRepository);
        self::assertSame('path', $frameworkRepository['type'] ?? null);
        self::assertSame('packages/framework', $frameworkRepository['url'] ?? null);
    }

    /** @return array<string, mixed> */
    private function manifest(string $path): array
    {
        $contents = file_get_contents($path);
        self::assertIsString($contents);
        $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);

        $stringKeyedManifest = [];
        foreach ($manifest as $key => $value) {
            if (is_string($key)) {
                $stringKeyedManifest[$key] = $value;
            }
        }

        return $stringKeyedManifest;
    }
}
