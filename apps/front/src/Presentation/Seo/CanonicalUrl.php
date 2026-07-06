<?php

declare(strict_types=1);

namespace App\Front\Presentation\Seo;

use Uri\Rfc3986\Uri;

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
        return $this->fromPathUsingBaseUrl(path: $path, baseUrl: $this->baseUrl);
    }

    public function fromPathWithFallbackBase(string $path, string $fallbackBaseUrl): ?string
    {
        return $this->fromPathUsingBaseUrl(
            path: $path,
            baseUrl: trim($this->baseUrl) !== '' ? $this->baseUrl : $fallbackBaseUrl,
        );
    }

    private function fromPathUsingBaseUrl(string $path, string $baseUrl): ?string
    {
        $baseUrl = rtrim(trim($baseUrl), '/');
        if ($baseUrl === '') {
            return null;
        }

        $baseUri = Uri::parse($baseUrl);
        if (!$baseUri instanceof Uri || $baseUri->getScheme() === null || $baseUri->getHost() === null) {
            return null;
        }

        $pathUri = Uri::parse($path);
        if (!$pathUri instanceof Uri || $pathUri->getScheme() !== null || $pathUri->getHost() !== null) {
            return null;
        }

        $pathOnly = $pathUri->getPath();
        $normalizedPath = '/' . ltrim($pathOnly !== '' ? $pathOnly : '/', '/');

        return $baseUri
            ->withPath($normalizedPath)
            ->withQuery(null)
            ->withFragment(null)
            ->toString();
    }
}
