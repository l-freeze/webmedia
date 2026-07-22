<?php

declare(strict_types=1);

final class ScaffoldFilesystemTransaction
{
    private readonly string $composerPath;

    private readonly ?string $originalComposer;

    private readonly string $stagingRoot;

    private bool $committed = false;

    public function __construct(
        private readonly string $finalRoot,
        string $projectRoot,
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

        $this->composerPath = rtrim($projectRoot, '/\\') . '/composer.json';
        $composerContents = is_file($this->composerPath) ? file_get_contents($this->composerPath) : null;
        if ($composerContents === false) {
            $this->removeStagingRoot();
            throw new RuntimeException("Failed to read Composer manifest: {$this->composerPath}");
        }
        $this->originalComposer = $composerContents;

        register_shutdown_function(function (): void {
            $this->rollback();
        });
    }

    public function stagingRoot(): string
    {
        return $this->stagingRoot;
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

        $this->restoreComposer();
        $this->removeStagingRoot();
    }

    private function stagePath(string $path): string
    {
        if ($path !== $this->finalRoot && !str_starts_with($path, $this->finalRoot . '/')) {
            throw new InvalidArgumentException("Scaffold path is outside its root: {$path}");
        }

        return $this->stagingRoot . substr($path, strlen($this->finalRoot));
    }

    private function restoreComposer(): void
    {
        if ($this->originalComposer === null) {
            return;
        }
        $currentComposer = is_file($this->composerPath) ? file_get_contents($this->composerPath) : null;
        if ($currentComposer === $this->originalComposer) {
            return;
        }

        $temporaryPath = $this->composerPath . '.pera-rollback-' . bin2hex(random_bytes(6));
        if (file_put_contents($temporaryPath, $this->originalComposer, LOCK_EX) === false) {
            return;
        }
        if (!rename($temporaryPath, $this->composerPath) && is_file($temporaryPath)) {
            unlink($temporaryPath);
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
