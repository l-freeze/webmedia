<?php

declare(strict_types=1);

namespace App\Framework\Http;

use InvalidArgumentException;

final readonly class Response
{
    /** @param array<string, string> $headers */
    public function __construct(
        public string $body,
        public int $status = 200,
        public array $headers = ['Content-Type' => 'text/html; charset=utf-8'],
    ) {
    }

    public static function redirect(string $location, int $status = 302): self
    {
        if (str_contains($location, "\r") || str_contains($location, "\n")) {
            throw new InvalidArgumentException('Redirect location must not contain line breaks');
        }

        return new self(
            body: '',
            status: $status,
            headers: ['Location' => $location],
        );
    }

    public function withHeader(string $name, string $value): self
    {
        return new self(
            body: $this->body,
            status: $this->status,
            headers: array_merge($this->headers, [$name => $value]),
        );
    }

    public function send(): void
    {
        http_response_code($this->status);
        header_remove('X-Powered-By');
        foreach ($this->headers as $name => $value) {
            if (!preg_match('/^[A-Za-z0-9!#$%&\'*+.^_`|~-]+$/', $name)) {
                throw new InvalidArgumentException("Invalid response header name: {$name}");
            }
            if (str_contains($value, "\r") || str_contains($value, "\n")) {
                throw new InvalidArgumentException("Invalid response header value for {$name}");
            }
            header("{$name}: {$value}");
        }
        echo $this->body;
    }
}
