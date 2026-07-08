<?php

declare(strict_types=1);

namespace App\FrontBusiness\Presentation;

final class BasePath
{
    private const VALUE = '/business';

    public static function value(): string
    {
        return self::VALUE;
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
}
