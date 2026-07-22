<?php

declare(strict_types=1);

namespace Pera\Framework\Tests\Package;

use PHPUnit\Framework\TestCase;

final class ComposerPackageBoundaryTest extends TestCase
{
    public function testFrameworkManifestOwnsRuntimeTestsAndExplicitScaffoldBinaries(): void
    {
        $root = dirname(__DIR__, 2);
        $manifest = $this->manifest(path: $root . '/composer.json');

        self::assertSame('pera/framework', $manifest['name'] ?? null);
        self::assertSame('library', $manifest['type'] ?? null);
        self::assertSame('MIT', $manifest['license'] ?? null);

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

        self::assertSame([
            'phpstan/phpstan' => '^2.1',
            'phpunit/phpunit' => '^11.0',
        ], $manifest['require-dev'] ?? null);

        $autoload = $manifest['autoload'] ?? null;
        self::assertIsArray($autoload);
        self::assertSame(['Pera\\Framework\\' => 'src/'], $autoload['psr-4'] ?? null);
        $autoloadDev = $manifest['autoload-dev'] ?? null;
        self::assertIsArray($autoloadDev);
        self::assertSame(['Pera\\Framework\\Tests\\' => 'tests/'], $autoloadDev['psr-4'] ?? null);
        self::assertSame([
            'bin/scaffold-app',
            'bin/scaffold-kernel',
            'bin/scaffold-shared',
        ], $manifest['bin'] ?? null);

        self::assertFileExists($root . '/bin/scaffold-transaction.php');
        self::assertFileExists($root . '/bin/scaffold-template-files.php');
        foreach (['kernel', 'shared', 'app/bare', 'app/minimal', 'app/todo', 'app/layered'] as $template) {
            self::assertDirectoryExists($root . '/templates/' . $template);
        }
        foreach (['API.md', 'CHANGELOG.md', 'LICENSE', 'SECURITY.md'] as $releaseDocument) {
            self::assertFileExists($root . '/' . $releaseDocument);
        }
        foreach (['phpunit.xml', 'phpstan.neon'] as $developmentPath) {
            self::assertFileExists($root . '/' . $developmentPath);
        }
        self::assertDirectoryExists($root . '/tests');

        self::assertFileDoesNotExist($root . '/src/Installer/Composer/FrameworkInstallerPlugin.php');
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
