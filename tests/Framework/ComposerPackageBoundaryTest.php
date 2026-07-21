<?php

declare(strict_types=1);

namespace Tests\Framework;

use PHPUnit\Framework\TestCase;

final class ComposerPackageBoundaryTest extends TestCase
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

    public function testFrameworkManifestOwnsRuntimeAndExplicitScaffoldBinaries(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = $this->manifest(path: $root . '/packages/framework/composer.json');

        self::assertSame('pera/framework', $manifest['name'] ?? null);
        self::assertSame('library', $manifest['type'] ?? null);

        $requirements = $manifest['require'] ?? null;
        self::assertSame([
            'php' => '>=8.5',
            'composer-runtime-api' => '^2.2',
            'ext-session' => '*',
            'ext-uri' => '*',
            'nikic/fast-route' => '^1.3',
            'psr/container' => '^2.0',
            'psr/log' => '^3.0',
        ], $requirements);

        $autoload = $manifest['autoload'] ?? null;
        self::assertIsArray($autoload);
        self::assertSame(
            ['Pera\\Framework\\' => 'src/'],
            $autoload['psr-4'] ?? null,
        );
        self::assertSame([
            'bin/scaffold-app',
            'bin/scaffold-kernel',
            'bin/scaffold-shared',
        ], $manifest['bin'] ?? null);

        self::assertFileExists($root . '/packages/framework/bin/scaffold-transaction.php');

        self::assertFileDoesNotExist(
            $root . '/packages/framework/src/Installer/Composer/FrameworkInstallerPlugin.php',
        );
        self::assertDirectoryDoesNotExist($root . '/src/Framework');
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
