<?php

declare(strict_types=1);

$normalize = static function (string $path): string {
    $normalized = rtrim($path, '/\\');

    return $normalized !== '' ? $normalized : DIRECTORY_SEPARATOR;
};

/**
 * Resolve the project receiving scaffold files.
 *
 * Composer vendor binaries expose the root project's autoloader through
 * `_composer_autoload_path`. Direct execution in this repository keeps the
 * historical repository-root behavior. Calling a packaged script directly,
 * without the Composer proxy, falls back to the current working directory.
 *
 * @return Closure(?string, string, ?string): string
 */
return static function (?string $composerAutoloadPath, string $sourceRoot, ?string $workingDirectory) use ($normalize): string {
    if ($composerAutoloadPath !== null && $composerAutoloadPath !== '' && is_file($composerAutoloadPath)) {
        return $normalize(dirname($composerAutoloadPath, 2));
    }

    if (
        is_file($sourceRoot . '/composer.json')
        && is_dir($sourceRoot . '/src/Framework')
        && is_dir($sourceRoot . '/apps/kernel')
    ) {
        return $normalize($sourceRoot);
    }

    if ($workingDirectory !== null && $workingDirectory !== '') {
        return $normalize($workingDirectory);
    }

    return $normalize($sourceRoot);
};
