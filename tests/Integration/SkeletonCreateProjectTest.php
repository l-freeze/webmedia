<?php

declare(strict_types=1);

namespace Tests\Integration;

use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;

final class SkeletonCreateProjectTest extends TestCase
{
    public function testLocalPathPackageCreatesRunnableProjectAndKeepsScaffoldsAvailable(): void
    {
        $repositoryRoot = dirname(__DIR__, 2);
        $temporaryRoot = sys_get_temp_dir() . '/pera-skeleton-create-project-' . bin2hex(random_bytes(4));
        $frameworkRoot = $temporaryRoot . '/framework';
        $skeletonRoot = $temporaryRoot . '/skeleton';
        $projectRoot = $temporaryRoot . '/example';
        self::assertTrue(mkdir($temporaryRoot));

        try {
            $sourceManifest = $this->manifest(path: $repositoryRoot . '/packages/skeleton/composer.json');
            self::assertSame('pera/skeleton', $sourceManifest['name'] ?? null);
            self::assertSame('project', $sourceManifest['type'] ?? null);
            self::assertSame('MIT', $sourceManifest['license'] ?? null);
            self::assertArrayNotHasKey('repositories', $sourceManifest);

            $this->copyDirectory(
                source: $repositoryRoot . '/packages/framework',
                destination: $frameworkRoot,
            );
            $this->copyDirectory(
                source: $repositoryRoot . '/packages/skeleton',
                destination: $skeletonRoot,
            );
            $this->addLocalFrameworkRepository(manifestPath: $skeletonRoot . '/composer.json', frameworkRoot: $frameworkRoot);

            $skeletonRepository = json_encode([
                'type' => 'path',
                'url' => $skeletonRoot,
                'options' => [
                    'symlink' => false,
                    'versions' => ['pera/skeleton' => '0.1.0'],
                ],
            ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $this->runCommand(
                command: [
                    'composer',
                    'create-project',
                    '--repository=' . $skeletonRepository,
                    '--no-interaction',
                    '--no-progress',
                    '--prefer-dist',
                    'pera/skeleton',
                    $projectRoot,
                    '0.1.0',
                ],
                workingDirectory: $temporaryRoot,
            );

            self::assertDirectoryExists($projectRoot . '/apps/kernel');
            self::assertDirectoryExists($projectRoot . '/apps/shared');
            self::assertDirectoryExists($projectRoot . '/apps/minimal');
            self::assertFileExists($projectRoot . '/public/index.php');
            self::assertFileExists($projectRoot . '/compose.yaml');
            self::assertFileExists($projectRoot . '/composer.lock');
            self::assertDirectoryExists($projectRoot . '/vendor/pera/framework');
            self::assertFalse(is_link($projectRoot . '/vendor/pera/framework'));
            foreach (['scaffold-app', 'scaffold-kernel', 'scaffold-shared'] as $binary) {
                self::assertFileExists($projectRoot . '/vendor/bin/' . $binary);
            }
            $sharedMiddleware = file_get_contents($projectRoot . '/apps/shared/config/middleware.php');
            self::assertIsString($sharedMiddleware);
            self::assertStringContainsString('CsrfMiddleware::class', $sharedMiddleware);
            self::assertStringContainsString("add CsrfMiddleware::class to each browser/session app's", $sharedMiddleware);

            $requirements = $this->requirements(manifestPath: $projectRoot . '/composer.json');
            self::assertSame('^0.1', $requirements['pera/framework'] ?? null);
            self::assertSame('^5.6', $requirements['vlucas/phpdotenv'] ?? null);
            self::assertSame('^3.0', $requirements['latte/latte'] ?? null);
            self::assertArrayNotHasKey('ext-pdo', $requirements);
            self::assertArrayNotHasKey('ext-pdo_sqlite', $requirements);

            $this->runCommand(command: ['composer', 'validate', '--strict'], workingDirectory: $projectRoot);
            $this->runCommand(command: ['composer', 'test'], workingDirectory: $projectRoot);
            $this->runCommand(command: ['composer', 'analyse'], workingDirectory: $projectRoot);

            $output = $this->runCommand(
                command: [PHP_BINARY, 'public/index.php'],
                workingDirectory: $projectRoot,
            );
            self::assertStringContainsString('minimal is running.', $output);

            $scaffoldOutput = $this->runCommand(
                command: [PHP_BINARY, 'vendor/bin/scaffold-app', 'todo', '/todo', '--template=todo'],
                workingDirectory: $projectRoot,
            );
            self::assertStringContainsString('Created app scaffold: apps/todo (todo)', $scaffoldOutput);
            self::assertDirectoryExists($projectRoot . '/apps/todo');
            $todoTemplate = file_get_contents($projectRoot . '/apps/todo/templates/index.latte');
            self::assertIsString($todoTemplate);
            self::assertSame(2, substr_count($todoTemplate, 'name="_csrf" value="{$csrfToken}"'));
            $requirements = $this->requirements(manifestPath: $projectRoot . '/composer.json');
            self::assertSame('*', $requirements['ext-pdo'] ?? null);
            self::assertSame('*', $requirements['ext-pdo_sqlite'] ?? null);
        } finally {
            $this->removeDirectory(path: $temporaryRoot);
        }
    }

    private function addLocalFrameworkRepository(string $manifestPath, string $frameworkRoot): void
    {
        $manifest = $this->manifest(path: $manifestPath);
        $manifest['repositories'] = [[
            'type' => 'path',
            'url' => $frameworkRoot,
            'options' => [
                'symlink' => false,
                'versions' => ['pera/framework' => '0.1.0'],
            ],
        ]];
        $contents = json_encode(
            $manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        self::assertNotFalse(file_put_contents($manifestPath, $contents . "\n"));
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

    /** @return array<string, mixed> */
    private function requirements(string $manifestPath): array
    {
        $manifest = $this->manifest(path: $manifestPath);
        $requirements = $manifest['require'] ?? null;
        self::assertIsArray($requirements);

        $stringKeyedRequirements = [];
        foreach ($requirements as $package => $constraint) {
            if (is_string($package)) {
                $stringKeyedRequirements[$package] = $constraint;
            }
        }

        return $stringKeyedRequirements;
    }

    /** @param list<string> $command */
    private function runCommand(array $command, string $workingDirectory): string
    {
        $descriptorSpec = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptorSpec, $pipes, $workingDirectory);
        if (!is_resource($process)) {
            throw new RuntimeException('Failed to start command: ' . implode(' ', $command));
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status !== 0) {
            self::fail(sprintf(
                "Command failed (%d): %s\n%s\n%s",
                $status,
                implode(' ', $command),
                is_string($stdout) ? $stdout : '',
                is_string($stderr) ? $stderr : '',
            ));
        }

        return is_string($stdout) ? $stdout : '';
    }

    private function copyDirectory(string $source, string $destination): void
    {
        self::assertTrue(mkdir($destination, recursive: true));
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            $relativePath = substr($item->getPathname(), strlen($source) + 1);
            if ($this->isDevelopmentArtifact(relativePath: $relativePath)) {
                continue;
            }
            $target = $destination . '/' . $relativePath;
            if ($item->isDir()) {
                self::assertTrue(mkdir($target));
                continue;
            }
            self::assertTrue(copy($item->getPathname(), $target));
            chmod($target, $item->getPerms() & 0777);
        }
    }

    private function isDevelopmentArtifact(string $relativePath): bool
    {
        $topLevelPath = explode('/', $relativePath, 2)[0];

        return in_array($topLevelPath, ['.git', '.phpunit.cache', 'vendor'], true)
            || in_array($relativePath, ['.phpunit.result.cache', 'composer.lock'], true);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($path);
    }
}
