<?php

declare(strict_types=1);

namespace App\Shared\Database;

use Stringable;

final readonly class PdoRow
{
    /** @param array<string, mixed> $values */
    private function __construct(private array $values)
    {
    }

    /** @param array<string, mixed> $values */
    public static function from(array $values): self
    {
        return new self(values: $values);
    }

    public function int(string $key, int $default = 0): int
    {
        $value = $this->values[$key] ?? null;
        if (is_int($value)) {
            return $value;
        }
        if (is_float($value)) {
            return (int) $value;
        }
        if (is_string($value) && is_numeric($value)) {
            return (int) $value;
        }

        return $default;
    }

    public function nullableInt(string $key): ?int
    {
        return array_key_exists($key, $this->values) && $this->values[$key] !== null
            ? $this->int(key: $key)
            : null;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->values[$key] ?? null;
        if (is_string($value)) {
            return $value;
        }
        if (is_int($value) || is_float($value) || is_bool($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return $default;
    }

    public function nullableString(string $key): ?string
    {
        return array_key_exists($key, $this->values) && $this->values[$key] !== null
            ? $this->string(key: $key)
            : null;
    }

    public function date(string $key, string $format = 'Y-m-d', string $default = ''): string
    {
        $value = $this->string(key: $key);
        if ($value === '') {
            return $default;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $default;
        }

        return date($format, $timestamp);
    }
}
