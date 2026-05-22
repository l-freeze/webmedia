<?php

declare(strict_types=1);

namespace App\Framework\Http;

final readonly class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $server
     */
    public function __construct(
        public string $method,
        public string $path,
        public array $query,
        public array $body,
        public array $server,
    ) {
    }

    public static function fromGlobals(): self
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $path,
            array_map('strval', $_GET),
            $_POST,
            array_map(static fn (mixed $value): string => is_scalar($value) ? (string) $value : '', $_SERVER),
        );
    }

    public function input(string $key, string $default = ''): string
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;
        return is_scalar($value) ? trim((string) $value) : $default;
    }
}
