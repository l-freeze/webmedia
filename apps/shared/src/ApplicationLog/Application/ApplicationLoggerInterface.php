<?php

declare(strict_types=1);

namespace App\Shared\ApplicationLog\Application;

interface ApplicationLoggerInterface
{
    /** @param array<string, mixed> $context */
    public function log(string $severity, string $message, array $context = []): void;
}
