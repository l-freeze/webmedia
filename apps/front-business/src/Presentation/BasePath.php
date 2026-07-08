<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation;

final class BasePath
{
    private static ?string $basePath = null;

    public static function value(): string
    {
        self::$basePath ??= self::load();

        return self::$basePath;
    }

    public static function path(string $path = ''): string
    {
        $path = '/' . ltrim($path, '/');
        $basePath = self::value();

        if ($basePath === '/') {
            return $path;
        }

        return $basePath . ($path === '/' ? '' : $path);
    }

    private static function load(): string
    {
        $value = require dirname(__DIR__, 2) . '/config/base_path.php';
        if (!is_string($value)) {
            return '/';
        }

        $value = '/' . trim($value, '/');

        return $value === '/' ? '/' : rtrim($value, '/');
    }
}
