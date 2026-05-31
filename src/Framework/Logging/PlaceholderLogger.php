<?php

declare(strict_types=1);

namespace App\Framework\Logging;

use LogicException;
use Psr\Log\AbstractLogger;
use Stringable;

final class PlaceholderLogger extends AbstractLogger
{
    /** @param array<string, mixed> $context */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        throw new LogicException('Logger is not configured. Bind Psr\Log\LoggerInterface to a real logger before writing logs.');
    }
}
