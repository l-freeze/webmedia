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
 * `_composer_autoload_path`. Calling a package binary directly without the
 * Composer proxy falls back to the current working directory. The package
 * root is used only when a working directory is unavailable.
 *
 * @return Closure(?string, string, ?string): string
 */
return static function (?string $composerAutoloadPath, string $packageRoot, ?string $workingDirectory) use ($normalize): string {
    if ($composerAutoloadPath !== null && $composerAutoloadPath !== '' && is_file($composerAutoloadPath)) {
        $resolvedAutoloadPath = realpath($composerAutoloadPath);

        return $normalize(dirname($resolvedAutoloadPath !== false ? $resolvedAutoloadPath : $composerAutoloadPath, 2));
    }

    if ($workingDirectory !== null && $workingDirectory !== '') {
        return $normalize($workingDirectory);
    }

    return $normalize($packageRoot);
};
