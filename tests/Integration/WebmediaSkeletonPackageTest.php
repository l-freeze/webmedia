<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/tools/WebmediaSkeletonBuilder.php';

final class WebmediaSkeletonPackageTest extends TestCase
{
    public function testDistributionBoundaryIsSelfContainedAndProprietary(): void
    {
        $root = dirname(__DIR__, 2) . '/packages/webmedia-skeleton';
        $builder = new \WebmediaSkeletonBuilder();
        $builder->validate(root: $root);

        $manifestContents = file_get_contents($root . '/composer.json');
        self::assertIsString($manifestContents);
        $manifest = json_decode($manifestContents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($manifest);

        self::assertSame('pera/webmedia-skeleton', $manifest['name'] ?? null);
        self::assertSame('project', $manifest['type'] ?? null);
        self::assertSame('proprietary', $manifest['license'] ?? null);
        self::assertArrayNotHasKey('repositories', $manifest);
        $requirements = $manifest['require'] ?? null;
        self::assertIsArray($requirements);
        self::assertSame('^0.1', $requirements['pera/framework'] ?? null);
        $autoload = $manifest['autoload'] ?? null;
        self::assertIsArray($autoload);
        $psr4 = $autoload['psr-4'] ?? null;
        self::assertIsArray($psr4);
        self::assertArrayNotHasKey('App\\FrontBusiness\\', $psr4);

        self::assertDirectoryExists($root . '/apps/kernel');
        self::assertDirectoryExists($root . '/apps/shared');
        self::assertDirectoryExists($root . '/apps/front');
        self::assertDirectoryExists($root . '/apps/cms');
        self::assertDirectoryDoesNotExist($root . '/apps/front-business');
        self::assertDirectoryDoesNotExist($root . '/packages');
        self::assertFileExists($root . '/database/migrations/20260523000100_create_initial_schema.php');
        self::assertFileExists($root . '/docker-compose.yml');
        foreach ([
            '.gitattributes',
            'LICENSE',
            'README.md',
            'composer.json',
            'phpstan.neon',
            'phpunit.xml',
        ] as $generatedFile) {
            $contents = file_get_contents($root . '/' . $generatedFile);
            self::assertIsString($contents);
            self::assertStringEndsWith("\n", $contents, "{$generatedFile} must end with a newline.");
        }
    }

    public function testDifferenceDetectionReportsMissingChangedAndUnexpectedFiles(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/webmedia-skeleton-difference-' . bin2hex(random_bytes(4));
        $expectedRoot = $temporaryRoot . '/expected';
        $actualRoot = $temporaryRoot . '/actual';
        self::assertTrue(mkdir($expectedRoot, 0775, true));
        self::assertTrue(mkdir($actualRoot, 0775, true));

        try {
            self::assertNotFalse(file_put_contents($expectedRoot . '/same.txt', 'same'));
            self::assertNotFalse(file_put_contents($actualRoot . '/same.txt', 'same'));
            self::assertNotFalse(file_put_contents($expectedRoot . '/changed.txt', 'expected'));
            self::assertNotFalse(file_put_contents($actualRoot . '/changed.txt', 'actual'));
            self::assertNotFalse(file_put_contents($expectedRoot . '/missing.txt', 'missing'));
            self::assertNotFalse(file_put_contents($actualRoot . '/unexpected.txt', 'unexpected'));

            $differences = (new \WebmediaSkeletonBuilder())->differences(
                expectedRoot: $expectedRoot,
                actualRoot: $actualRoot,
            );

            self::assertSame([
                'Changed: changed.txt',
                'Missing: missing.txt',
                'Unexpected: unexpected.txt',
            ], $differences);
        } finally {
            foreach ([$expectedRoot, $actualRoot] as $root) {
                foreach (glob($root . '/*') ?: [] as $path) {
                    unlink($path);
                }
                rmdir($root);
            }
            rmdir($temporaryRoot);
        }
    }

    public function testValidationRejectsDirectorySymlinks(): void
    {
        $temporaryRoot = sys_get_temp_dir() . '/webmedia-skeleton-symlink-' . bin2hex(random_bytes(4));
        $packageRoot = $temporaryRoot . '/package';
        $externalRoot = $temporaryRoot . '/external';
        self::assertTrue(mkdir($packageRoot, 0775, true));
        self::assertTrue(mkdir($externalRoot, 0775, true));
        self::assertNotFalse(file_put_contents(
            $packageRoot . '/composer.json',
            <<<'JSON'
{
    "name": "pera/webmedia-skeleton",
    "type": "project",
    "license": "proprietary",
    "require": {
        "pera/framework": "^0.1"
    }
}
JSON
        ));
        self::assertTrue(symlink($externalRoot, $packageRoot . '/linked-directory'));

        try {
            $this->expectException(\RuntimeException::class);
            $this->expectExceptionMessage('Webmedia Skeleton must not contain symlinks: linked-directory');
            (new \WebmediaSkeletonBuilder())->validate(root: $packageRoot);
        } finally {
            unlink($packageRoot . '/linked-directory');
            unlink($packageRoot . '/composer.json');
            rmdir($packageRoot);
            rmdir($externalRoot);
            rmdir($temporaryRoot);
        }
    }
}
