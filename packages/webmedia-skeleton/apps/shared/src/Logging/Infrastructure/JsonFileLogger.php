<?php

declare(strict_types=1);

namespace App\Shared\Logging\Infrastructure;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Stringable;
use Throwable;

class JsonFileLogger extends AbstractLogger
{
    private readonly JsonLineFileWriter $writer;

    public function __construct(
        string $path,
    ) {
        $this->writer = new JsonLineFileWriter(path: $path);
    }

    /** @param array<string, mixed> $context */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $record = [
            'timestamp' => gmdate('c'),
            'severity' => $this->severity(level: $level),
            'message' => (string) $message,
            'context' => $this->normalize(value: $context),
        ];

        $this->writer->write(record: $record);
    }

    private function severity(mixed $level): string
    {
        $levelName = $this->levelName(level: $level);

        return match ($levelName) {
            LogLevel::DEBUG => 'DEBUG',
            LogLevel::INFO => 'INFO',
            LogLevel::NOTICE => 'NOTICE',
            LogLevel::WARNING => 'WARNING',
            LogLevel::ERROR => 'ERROR',
            LogLevel::CRITICAL, LogLevel::ALERT, LogLevel::EMERGENCY => 'CRITICAL',
            default => strtoupper($levelName),
        };
    }

    private function levelName(mixed $level): string
    {
        if (is_scalar($level) || $level instanceof Stringable) {
            return (string) $level;
        }

        return get_debug_type($level);
    }

    private function normalize(mixed $value): mixed
    {
        if ($value instanceof Throwable) {
            return [
                'class' => $value::class,
                'message' => $value->getMessage(),
                'file' => $value->getFile(),
                'line' => $value->getLine(),
            ];
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                $normalized[is_int($key) ? $key : (string) $key] = $this->normalize(value: $item);
            }

            return $normalized;
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        return [
            'type' => get_debug_type($value),
        ];
    }
}
