<?php

declare(strict_types=1);

final class WebmediaSkeletonBuilder
{
    /** @var list<string> */
    private const INCLUDED_FILES = [
        '.env.example',
        '.gitignore',
        'Makefile',
        'docker-compose.yml',
        'package.json',
        'phinx.php',
        'pnpm-lock.yaml',
        'vite.config.js',
    ];

    /** @var list<string> */
    private const INCLUDED_PREFIXES = [
        '.docker/',
        'apps/cms/',
        'apps/front/',
        'apps/kernel/',
        'apps/shared/',
        'database/',
        'public/',
        'resources/',
        'storage/',
        'tests/Architecture/',
        'tests/Cms/',
        'tests/Front/',
        'tests/Kernel/',
        'tests/Shared/',
        'tests/Support/',
    ];

    /** @var list<string> */
    private const EXCLUDED_FILES = [
        'public/assets/css/front-business.css',
        'tests/Kernel/KernelScaffoldDriftTest.php',
    ];

    /**
     * @param list<string> $trackedPaths
     */
    public function generate(string $sourceRoot, string $destinationRoot, array $trackedPaths): void
    {
        $sourceRoot = rtrim($sourceRoot, '/\\');
        $destinationRoot = rtrim($destinationRoot, '/\\');
        $this->assertEmptyDestination(destinationRoot: $destinationRoot);

        $includedPaths = [];
        foreach ($trackedPaths as $relativePath) {
            $relativePath = str_replace('\\', '/', trim($relativePath));
            if (!$this->isIncluded(relativePath: $relativePath)) {
                continue;
            }
            $includedPaths[] = $relativePath;
        }
        sort($includedPaths);
        if ($includedPaths === []) {
            throw new RuntimeException('No tracked Webmedia source files matched the Skeleton allowlist.');
        }

        foreach ($includedPaths as $relativePath) {
            $sourcePath = $sourceRoot . '/' . $relativePath;
            if (!is_file($sourcePath)) {
                throw new RuntimeException("Tracked Skeleton source file does not exist: {$relativePath}");
            }
            $this->copyFile(
                sourcePath: $sourcePath,
                destinationPath: $destinationRoot . '/' . $relativePath,
            );
        }

        $this->writeDistributionFiles(sourceRoot: $sourceRoot, destinationRoot: $destinationRoot);
        $this->transformKernelApplication(destinationRoot: $destinationRoot);
        $this->transformDevelopmentWorkerPreset(destinationRoot: $destinationRoot);
        $this->validate(root: $destinationRoot);
    }

    /**
     * @return list<string>
     */
    public function differences(string $expectedRoot, string $actualRoot): array
    {
        if (!is_dir($actualRoot)) {
            return ['Missing package root: ' . $actualRoot];
        }

        $expected = $this->fileHashes(root: $expectedRoot);
        $actual = $this->fileHashes(root: $actualRoot);
        $differences = [];

        foreach ($expected as $path => $hash) {
            if (!array_key_exists($path, $actual)) {
                $differences[] = "Missing: {$path}";
                continue;
            }
            if ($actual[$path] !== $hash) {
                $differences[] = "Changed: {$path}";
            }
        }
        foreach ($actual as $path => $_hash) {
            if (!array_key_exists($path, $expected)) {
                $differences[] = "Unexpected: {$path}";
            }
        }
        sort($differences);

        return $differences;
    }

    public function publish(string $candidateRoot, string $targetRoot): void
    {
        $candidateRoot = rtrim($candidateRoot, '/\\');
        $targetRoot = rtrim($targetRoot, '/\\');
        $this->validate(root: $candidateRoot);

        $backupRoot = dirname($targetRoot) . '/.webmedia-skeleton-backup-' . bin2hex(random_bytes(6));
        $hasCurrentTarget = is_dir($targetRoot);
        if ($hasCurrentTarget && !rename($targetRoot, $backupRoot)) {
            throw new RuntimeException("Failed to stage the current Webmedia Skeleton for replacement: {$targetRoot}");
        }

        try {
            if (!rename($candidateRoot, $targetRoot)) {
                throw new RuntimeException("Failed to publish the verified Webmedia Skeleton: {$targetRoot}");
            }
        } catch (Throwable $throwable) {
            if ($hasCurrentTarget && is_dir($backupRoot) && !is_dir($targetRoot)) {
                rename($backupRoot, $targetRoot);
            }
            throw $throwable;
        }

        if (is_dir($backupRoot)) {
            $this->removeDirectory(root: $backupRoot);
        }
    }

    public function validate(string $root): void
    {
        $root = rtrim($root, '/\\');
        $manifestPath = $root . '/composer.json';
        $contents = file_get_contents($manifestPath);
        if (!is_string($contents)) {
            throw new RuntimeException("Webmedia Skeleton Composer manifest is missing: {$manifestPath}");
        }
        $manifest = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest)) {
            throw new RuntimeException('Webmedia Skeleton Composer manifest must be an object.');
        }
        if (($manifest['name'] ?? null) !== 'pera/webmedia-skeleton') {
            throw new RuntimeException('Webmedia Skeleton package name must be pera/webmedia-skeleton.');
        }
        if (($manifest['type'] ?? null) !== 'project' || ($manifest['license'] ?? null) !== 'proprietary') {
            throw new RuntimeException('Webmedia Skeleton must be a proprietary Composer project.');
        }
        if (array_key_exists('repositories', $manifest)) {
            throw new RuntimeException('Distributed Webmedia Skeleton must not contain monorepo repository declarations.');
        }
        $requirements = $manifest['require'] ?? null;
        if (!is_array($requirements) || ($requirements['pera/framework'] ?? null) !== '^0.1') {
            throw new RuntimeException('Distributed Webmedia Skeleton must require pera/framework:^0.1.');
        }

        $forbiddenPaths = [
            '.env',
            'apps/front-business',
            'node_modules',
            'packages',
            'public/assets/css/front-business.css',
            'vendor',
        ];
        foreach ($forbiddenPaths as $forbiddenPath) {
            if (file_exists($root . '/' . $forbiddenPath)) {
                throw new RuntimeException("Forbidden path is present in Webmedia Skeleton: {$forbiddenPath}");
            }
        }

        foreach ($this->files(root: $root) as $relativePath => $absolutePath) {
            if (is_link($absolutePath)) {
                throw new RuntimeException("Webmedia Skeleton must not contain symlinks: {$relativePath}");
            }
            $fileContents = file_get_contents($absolutePath);
            if (!is_string($fileContents) || str_contains($fileContents, "\0")) {
                continue;
            }
            foreach ([
                '/home/yamamoto/webmedia',
                '/app/packages/framework',
                '"type": "path"',
                'App\\\\FrontBusiness',
                'apps/front-business',
            ] as $forbiddenText) {
                if (str_contains($fileContents, $forbiddenText)) {
                    throw new RuntimeException(
                        "Forbidden monorepo or POC reference {$forbiddenText} in Webmedia Skeleton file: {$relativePath}",
                    );
                }
            }
        }
    }

    private function isIncluded(string $relativePath): bool
    {
        if (in_array($relativePath, self::EXCLUDED_FILES, true)) {
            return false;
        }
        if (in_array($relativePath, self::INCLUDED_FILES, true)) {
            return true;
        }
        foreach (self::INCLUDED_PREFIXES as $prefix) {
            if (str_starts_with($relativePath, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function assertEmptyDestination(string $destinationRoot): void
    {
        if (!is_dir($destinationRoot)) {
            if (!mkdir($destinationRoot, 0775, true) && !is_dir($destinationRoot)) {
                throw new RuntimeException("Failed to create Webmedia Skeleton candidate root: {$destinationRoot}");
            }

            return;
        }

        $entries = scandir($destinationRoot);
        if (!is_array($entries)) {
            throw new RuntimeException("Failed to inspect Webmedia Skeleton candidate root: {$destinationRoot}");
        }
        if (array_values(array_diff($entries, ['.', '..'])) !== []) {
            throw new RuntimeException("Webmedia Skeleton candidate root must be empty: {$destinationRoot}");
        }
    }

    private function copyFile(string $sourcePath, string $destinationPath): void
    {
        $directory = dirname($destinationPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create Webmedia Skeleton directory: {$directory}");
        }
        if (!copy($sourcePath, $destinationPath)) {
            throw new RuntimeException("Failed to copy Webmedia Skeleton source file: {$sourcePath}");
        }
        $permissions = fileperms($sourcePath);
        if (is_int($permissions)) {
            chmod($destinationPath, $permissions & 0777);
        }
    }

    private function writeDistributionFiles(string $sourceRoot, string $destinationRoot): void
    {
        $sourceManifestContents = file_get_contents($sourceRoot . '/composer.json');
        if (!is_string($sourceManifestContents)) {
            throw new RuntimeException('Root Composer manifest is missing.');
        }
        $manifest = json_decode($sourceManifestContents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($manifest)) {
            throw new RuntimeException('Root Composer manifest must be an object.');
        }

        $manifest['name'] = 'pera/webmedia-skeleton';
        $manifest['description'] = 'A production-oriented WEB media and CMS project skeleton for Pera.';
        $manifest['type'] = 'project';
        $manifest['license'] = 'proprietary';
        unset($manifest['repositories']);
        if (!isset($manifest['require']) || !is_array($manifest['require'])) {
            throw new RuntimeException('Root Composer runtime requirements are invalid.');
        }
        $manifest['require']['pera/framework'] = '^0.1';
        ksort($manifest['require']);
        $autoload = $manifest['autoload'] ?? null;
        if (!is_array($autoload)) {
            throw new RuntimeException('Root Composer autoload configuration is invalid.');
        }
        $psr4 = $autoload['psr-4'] ?? null;
        if (!is_array($psr4)) {
            throw new RuntimeException('Root Composer PSR-4 configuration is invalid.');
        }
        unset($psr4['App\\FrontBusiness\\']);
        ksort($psr4);
        $autoload['psr-4'] = $psr4;
        $manifest['autoload'] = $autoload;

        $autoloadDev = $manifest['autoload-dev'] ?? null;
        if (is_array($autoloadDev)) {
            $psr4Dev = $autoloadDev['psr-4'] ?? null;
            if (is_array($psr4Dev)) {
                unset($psr4Dev['Pera\\Framework\\Tests\\']);
                ksort($psr4Dev);
                $autoloadDev['psr-4'] = $psr4Dev;
                $manifest['autoload-dev'] = $autoloadDev;
            }
        }
        $this->write(
            path: $destinationRoot . '/composer.json',
            contents: json_encode(
                $manifest,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ) . "\n",
        );

        $this->write(path: $destinationRoot . '/phpunit.xml', contents: <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php" colors="true">
  <testsuites>
    <testsuite name="Application">
      <directory>tests</directory>
    </testsuite>
  </testsuites>
</phpunit>
XML
            . "\n",
        );
        $this->write(path: $destinationRoot . '/phpstan.neon', contents: <<<'NEON'
parameters:
    level: max
    paths:
        - apps
        - database
        - phinx.php
        - public
        - tests
    tmpDir: storage/phpstan
NEON
            . "\n",
        );
        $this->write(path: $destinationRoot . '/README.md', contents: <<<'MARKDOWN'
# Pera Webmedia Skeleton

This proprietary project skeleton provides a production-oriented WEB media and CMS built on `pera/framework`.

## Development

```bash
cp .env.example .env
docker compose build app
docker compose run --rm app composer install
docker compose up -d db
docker compose run --rm app composer migrate
docker compose run --rm app composer seed
docker compose up -d app
```

The Front application is available at <http://localhost:8080/> and the CMS login is available at <http://localhost:8080/admin/login>.

The development seed creates `admin@example.com` with password `password`. Replace or remove development credentials before deployment. Do not run development seeds in production.

## Verification

```bash
docker compose run --rm app composer validate --strict
docker compose run --rm app composer test
docker compose run --rm app composer analyse
docker compose run --rm app composer check-platform-reqs
```

The generated Kernel, Shared, Front, and CMS source is owned and maintained by the consuming project. Framework runtime updates remain available through Composer.

## License

This package is proprietary. Use and redistribution require a separate commercial license.
MARKDOWN
            . "\n",
        );
        $this->write(path: $destinationRoot . '/LICENSE', contents: <<<'TEXT'
Copyright (c) l-freeze. All rights reserved.

This software is proprietary. Use, modification, and redistribution are
permitted only under a separate written commercial license.
TEXT
            . "\n",
        );
        $this->write(path: $destinationRoot . '/.gitattributes', contents: <<<'ATTRIBUTES'
/.gitattributes export-ignore
ATTRIBUTES
            . "\n",
        );
    }

    private function transformKernelApplication(string $destinationRoot): void
    {
        $path = $destinationRoot . '/apps/kernel/config/application.php';
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            throw new RuntimeException('Generated Webmedia Skeleton Kernel application config is missing.');
        }
        $frontBusinessDefinition = <<<'PHP'
        new ApplicationDefinition(
            name: 'front-business',
            pathPrefix: '/business',
            routesPath: '/apps/front-business/config/routes.php',
            containerPath: '/apps/front-business/config/container.php',
            middlewarePath: '/apps/front-business/config/middleware.php',
        ),

PHP;
        if (substr_count($contents, $frontBusinessDefinition) !== 1) {
            throw new RuntimeException('Expected exactly one Front Business Kernel definition during Skeleton generation.');
        }
        $contents = str_replace($frontBusinessDefinition, '', $contents);
        $this->write(path: $path, contents: $contents);
    }

    private function transformDevelopmentWorkerPreset(string $destinationRoot): void
    {
        $path = $destinationRoot . '/.docker/caddy/worker.dev.caddyfile';
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            throw new RuntimeException('Generated Webmedia Skeleton development worker preset is missing.');
        }
        $contents = str_replace(
            'watch /app/packages/framework/src',
            'watch /app/vendor/pera/framework/src',
            $contents,
            $count,
        );
        if ($count !== 1) {
            throw new RuntimeException('Expected exactly one Framework watch path during Skeleton generation.');
        }
        $this->write(path: $path, contents: $contents);
    }

    private function write(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Failed to create directory for generated file: {$directory}");
        }
        if (file_put_contents($path, $contents, LOCK_EX) === false) {
            throw new RuntimeException("Failed to write generated Webmedia Skeleton file: {$path}");
        }
    }

    /**
     * @return array<string, string>
     */
    private function fileHashes(string $root): array
    {
        $hashes = [];
        foreach ($this->files(root: $root) as $relativePath => $absolutePath) {
            $hash = hash_file('sha256', $absolutePath);
            if (!is_string($hash)) {
                throw new RuntimeException("Failed to hash Webmedia Skeleton file: {$absolutePath}");
            }
            $hashes[$relativePath] = $hash;
        }

        return $hashes;
    }

    /**
     * @return array<string, string>
     */
    private function files(string $root): array
    {
        $root = rtrim($root, '/\\');
        if (!is_dir($root)) {
            throw new RuntimeException("Webmedia Skeleton directory does not exist: {$root}");
        }
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            if ($item->isLink()) {
                $relativePath = str_replace('\\', '/', substr($item->getPathname(), strlen($root) + 1));
                throw new RuntimeException("Webmedia Skeleton must not contain symlinks: {$relativePath}");
            }
            if (!$item->isFile()) {
                continue;
            }
            $absolutePath = $item->getPathname();
            $relativePath = str_replace('\\', '/', substr($absolutePath, strlen($root) + 1));
            $files[$relativePath] = $absolutePath;
        }
        ksort($files);

        return $files;
    }

    private function removeDirectory(string $root): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($root);
    }
}
