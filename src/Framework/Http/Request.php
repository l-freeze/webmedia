<?php

declare(strict_types=1);

namespace App\Framework\Http;

final readonly class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $server
     * @param array<string, mixed> $files
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query,
        public array $body,
        public array $server,
        public array $files = [],
    ) {
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
        return is_scalar($value) ? trim((string) $value) : $default;
    }
}
