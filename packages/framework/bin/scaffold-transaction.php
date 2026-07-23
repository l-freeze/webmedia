<?php

declare(strict_types=1);

final class ScaffoldFilesystemTransaction
{
    private readonly string $stagingRoot;

    /** @var array<string, string|null> */
    private array $originalFiles = [];

    private bool $committed = false;

    public function __construct(
        private readonly string $finalRoot,
        private readonly string $projectRoot,
    ) {
        if (file_exists($this->finalRoot)) {
            throw new RuntimeException("Refusing to overwrite existing scaffold root: {$this->finalRoot}");
        }

        $parent = dirname($this->finalRoot);
        if (!is_dir($parent) && !mkdir($parent, 0775, true) && !is_dir($parent)) {
            throw new RuntimeException("Failed to create scaffold parent directory: {$parent}");
        }

        $this->stagingRoot = $parent . '/.pera-' . basename($this->finalRoot) . '-' . bin2hex(random_bytes(6));
        if (!mkdir($this->stagingRoot, 0775) && !is_dir($this->stagingRoot)) {
            throw new RuntimeException("Failed to create scaffold staging directory: {$this->stagingRoot}");
        }

        try {
            $this->watchFile(path: rtrim($this->projectRoot, '/\\') . '/composer.json');
        } catch (Throwable $throwable) {
            $this->removeStagingRoot();
            throw $throwable;
        }

        register_shutdown_function(function (): void {
            $this->rollback();
        });
    }

    public function stagingRoot(): string
    {
        return $this->stagingRoot;
    }

    public function watchFile(string $path): void
    {
        $projectRoot = rtrim($this->projectRoot, '/\\');
        if ($path !== $projectRoot && !str_starts_with($path, $projectRoot . '/')) {
            throw new InvalidArgumentException("Scaffold transaction file is outside the project root: {$path}");
        }
        if (array_key_exists($path, $this->originalFiles)) {
            return;
        }

        $contents = is_file($path) ? file_get_contents($path) : null;
        if ($contents === false) {
            throw new RuntimeException("Failed to read project file: {$path}");
        }
        $this->originalFiles[$path] = $contents;
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    public function stagePaths(array $paths): array
    {
        $staged = [];
        foreach ($paths as $path) {
            $staged[] = $this->stagePath(path: $path);
        }

        return $staged;
    }

    /**
     * @param array<string, string> $files
     * @return array<string, string>
     */
    public function stageFiles(array $files): array
    {
        $staged = [];
        foreach ($files as $path => $contents) {
            $staged[$this->stagePath(path: $path)] = $contents;
        }

        return $staged;
    }

    public function commit(): void
    {
        if (file_exists($this->finalRoot)) {
            throw new RuntimeException("Scaffold root appeared while generating: {$this->finalRoot}");
        }
        if (!rename($this->stagingRoot, $this->finalRoot)) {
            throw new RuntimeException("Failed to publish scaffold root: {$this->finalRoot}");
        }

        $this->committed = true;
    }

    public function rollback(): void
    {
        if ($this->committed) {
            return;
        }

        $this->restoreFiles();
        $this->removeStagingRoot();
    }

    private function stagePath(string $path): string
    {
        if ($path !== $this->finalRoot && !str_starts_with($path, $this->finalRoot . '/')) {
            throw new InvalidArgumentException("Scaffold path is outside its root: {$path}");
        }

        return $this->stagingRoot . substr($path, strlen($this->finalRoot));
    }

    private function restoreFiles(): void
    {
        foreach ($this->originalFiles as $path => $originalContents) {
            $currentContents = is_file($path) ? file_get_contents($path) : null;
            if ($currentContents === $originalContents) {
                continue;
            }
            if ($originalContents === null) {
                if (is_file($path)) {
                    unlink($path);
                }
                continue;
            }

            $temporaryPath = $path . '.pera-rollback-' . bin2hex(random_bytes(6));
            if (file_put_contents($temporaryPath, $originalContents, LOCK_EX) === false) {
                continue;
            }
            if (!rename($temporaryPath, $path) && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private function removeStagingRoot(): void
    {
        if (!is_dir($this->stagingRoot)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->stagingRoot, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($iterator as $item) {
            if (!$item instanceof SplFileInfo) {
                continue;
            }
            $path = $item->getPathname();
            $item->isDir() ? rmdir($path) : unlink($path);
        }
        rmdir($this->stagingRoot);
    }
}

/** @return Closure(string, string): ScaffoldFilesystemTransaction */
return static fn (string $projectRoot, string $finalRoot): ScaffoldFilesystemTransaction => new ScaffoldFilesystemTransaction(
    finalRoot: $finalRoot,
    projectRoot: $projectRoot,
);
