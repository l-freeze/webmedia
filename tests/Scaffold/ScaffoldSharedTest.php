<?php

declare(strict_types=1);

namespace Tests\Scaffold;

use Pera\Framework\Container\Container;
use Pera\Framework\Logging\PlaceholderLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ScaffoldSharedTest extends TestCase
{
    public function testSharedContainerStartsWithPlaceholderLoggerBinding(): void
    {
        $root = $this->temporaryRoot();

        try {
            $output = $this->runScaffold(root: $root);

            self::assertStringContainsString('Created shared scaffold: apps/shared', $output);

            $containerConfigPath = $root . '/apps/shared/config/container.php';
            self::assertFileExists($containerConfigPath);

            $configureContainer = require $containerConfigPath;
            self::assertIsCallable($configureContainer);

            $container = new Container();
            $configureContainer($container, $root);

            self::assertInstanceOf(
                PlaceholderLogger::class,
                $container->get(id: LoggerInterface::class),
            );

            $readme = $this->read(path: $root . '/apps/shared/README.md');
            self::assertStringContainsString('This is not a real logger.', $readme);
            self::assertStringContainsString('Psr\Log\NullLogger', $readme);
            self::assertStringContainsString('The Framework package itself does not require Latte.', $readme);
            self::assertStringNotContainsString('PDO helpers', $readme);
            self::assertFileDoesNotExist($root . '/apps/shared/src/Database/PdoHelper.php');
            self::assertFileDoesNotExist($root . '/apps/shared/src/Database/PdoRow.php');

            self::assertSame('^3.0', $this->composerRuntimeRequirement(root: $root, package: 'latte/latte'));
            self::assertStringContainsString('Updated composer.json runtime requirements:', $output);
            self::assertStringContainsString("composer update\n", $output . "\n");
        } finally {
            $this->removeDirectory(path: $root);
        }
    }

    private function temporaryRoot(): string
    {
        $root = sys_get_temp_dir() . '/webmedia-scaffold-shared-test-' . bin2hex(random_bytes(4));
        self::assertTrue(mkdir($root, recursive: true));
        self::assertNotFalse(file_put_contents(
            $root . '/composer.json',
            "{\n  \"name\": \"example/app\",\n  \"require\": {\n    \"php\": \"^8.5\"\n  }\n}\n",
        ));

        return $root;
    }

    private function composerRuntimeRequirement(string $root, string $package): ?string
    {
        $contents = $this->read(path: $root . '/composer.json');
        $composer = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($composer);
        $requirements = $composer['require'] ?? [];
        self::assertIsArray($requirements);
        $constraint = $requirements[$package] ?? null;
        self::assertTrue($constraint === null || is_string($constraint));

        return $constraint;
    }

    private function runScaffold(string $root): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $command = implode(' ', [
            escapeshellarg(PHP_BINARY),
            escapeshellarg($projectRoot . '/packages/framework/bin/scaffold-shared'),
            '--root=' . escapeshellarg($root),
            '2>&1',
        ]);

        $lines = [];
        exec($command, $lines, $status);
        $output = implode("\n", $lines);

        self::assertSame(0, $status, $output);

        return $output;
    }

    private function read(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertIsString($contents);

        return $contents;
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        self::assertIsArray($items);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path . '/' . $item;
            if (is_dir($child)) {
                $this->removeDirectory(path: $child);
                continue;
            }

            unlink($child);
        }

        rmdir($path);
    }
}
