<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Seo;

final readonly class CanonicalUrl
{
    public function __construct(private string $baseUrl)
    {
    }

    public function article(string $slug): ?string
    {
        return $this->fromPath('/articles/' . ltrim($slug, '/'));
    }

    public function category(string $slug): ?string
    {
        return $this->fromPath('/categories/' . ltrim($slug, '/'));
    }

    public function tag(string $slug): ?string
    {
        return $this->fromPath('/tags/' . ltrim($slug, '/'));
    }

    public function page(string $slug): ?string
    {
        return $this->fromPath('/pages/' . ltrim($slug, '/'));
    }

    public function fromPath(string $path): ?string
    {
        $baseUrl = rtrim(trim($this->baseUrl), '/');
        if ($baseUrl === '') {
            return null;
        }

        $pathOnly = parse_url($path, PHP_URL_PATH);
        $normalizedPath = '/' . ltrim(is_string($pathOnly) && $pathOnly !== '' ? $pathOnly : '/', '/');

        return $baseUrl . $normalizedPath;
    }
}
