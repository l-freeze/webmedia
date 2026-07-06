<?php

declare(strict_types=1);

namespace App\Framework\Http;

use InvalidArgumentException;
use Uri\Rfc3986\Uri;

final readonly class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $server
     * @param array<string, mixed> $files
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query,
        public array $body,
        public array $server,
        public array $files = [],
        public array $attributes = [],
    ) {
    }

    public static function fromGlobals(): self
    {
        $server = self::stringMap(values: $_SERVER);
        $requestUri = $server['REQUEST_URI'] ?? '/';
        $path = self::pathFromRequestUri(requestUri: $requestUri);

        return new self(
            method: strtoupper($server['REQUEST_METHOD'] ?? 'GET'),
            path: $path,
            query: self::stringMap(values: $_GET),
            body: self::mixedMap(values: $_POST),
            server: $server,
            files: self::mixedMap(values: $_FILES),
        );
    }

    private static function pathFromRequestUri(string $requestUri): string
    {
        $uri = Uri::parse($requestUri);
        if (!$uri instanceof Uri) {
            throw new InvalidArgumentException('Request URI is malformed.');
        }

        $path = $uri->getPath();
        return $path !== '' ? $path : '/';
    }

    /**
     * @param array<mixed> $values
     * @return array<string, string>
     */
    public static function stringMap(array $values): array
    {
        $strings = [];
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $strings[$key] = is_scalar($value) ? (string) $value : '';
        }

        return $strings;
    }

    /**
     * @param array<mixed> $values
     * @return array<string, mixed>
     */
    public static function mixedMap(array $values): array
    {
        $mapped = [];
        foreach ($values as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            $mapped[$key] = $value;
        }

        return $mapped;
    }

    public function isHttps(): bool
    {
        $https = $this->server['HTTPS'] ?? '';
        $forwardedProto = $this->server['HTTP_X_FORWARDED_PROTO'] ?? '';

        return ($https !== '' && $https !== 'off')
            || strtolower($forwardedProto) === 'https';
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;
        return is_scalar($value) ? (string) $value : $default;
    }

    public function bodyInput(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $default;
        return is_scalar($value) ? (string) $value : $default;
    }

    public function queryInput(string $key, string $default = ''): string
    {
        return $this->query[$key] ?? $default;
    }

    public function attribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function withAttribute(string $key, mixed $value): self
    {
        return new self(
            method: $this->method,
            path: $this->path,
            query: $this->query,
            body: $this->body,
            server: $this->server,
            files: $this->files,
            attributes: array_merge($this->attributes, [$key => $value]),
        );
    }
}
