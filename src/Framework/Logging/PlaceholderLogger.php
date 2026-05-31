<?php

declare(strict_types=1);

namespace App\Framework\Logging;

use LogicException;

final readonly class PlaceholderLogger implements LoggerInterface
{
    /** @param array<string, mixed> $context */
    public function debug(string $message, array $context = []): void
    {
        $this->notConfigured();
    }

    /** @param array<string, mixed> $context */
    public function info(string $message, array $context = []): void
    {
        $this->notConfigured();
    }

    /** @param array<string, mixed> $context */
    public function warning(string $message, array $context = []): void
    {
        $this->notConfigured();
    }

    /** @param array<string, mixed> $context */
    public function error(string $message, array $context = []): void
    {
        $this->notConfigured();
    }

    private function notConfigured(): never
    {
        throw new LogicException('Logger is not configured. Bind LoggerInterface to a real logger before writing logs.');
    }
}
