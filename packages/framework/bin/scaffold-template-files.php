<?php

declare(strict_types=1);

/**
 * Load a versioned scaffold template directory.
 *
 * @param array<string, string> $variables
 * @return array<string, string> relative path => rendered contents
 */
return static function (string $templateRoot, array $variables = []): array {
    if (!is_dir($templateRoot)) {
        throw new RuntimeException("Scaffold template directory was not found: {$templateRoot}");
    }

    $search = [];
    $replace = [];
    foreach ($variables as $name => $value) {
        $search[] = '{{ ' . $name . ' }}';
        $replace[] = $value;
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($templateRoot, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }
        $path = $file->getPathname();
        $contents = file_get_contents($path);
        if (!is_string($contents)) {
            throw new RuntimeException("Failed to read scaffold template: {$path}");
        }
        $rendered = str_replace($search, $replace, $contents);
        if (preg_match('/{{ [a-zA-Z][a-zA-Z0-9_]* }}/', $rendered, $match) === 1) {
            throw new RuntimeException("Unresolved scaffold template variable {$match[0]} in {$path}");
        }

        $relativePath = substr($path, strlen(rtrim($templateRoot, '/\\')) + 1);
        $files[str_replace('\\', '/', $relativePath)] = $rendered;
    }
    ksort($files);
    if ($files === []) {
        throw new RuntimeException("Scaffold template directory is empty: {$templateRoot}");
    }

    return $files;
};
