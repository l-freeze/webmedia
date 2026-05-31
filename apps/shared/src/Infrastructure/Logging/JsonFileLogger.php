<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use JsonException;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use RuntimeException;
use Stringable;
use Throwable;

final class JsonFileLogger extends AbstractLogger
{
    public function __construct(
        private readonly string $path,
    ) {
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

        $this->write(record: $record);
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

    /** @param array<string, mixed> $record */
    private function write(array $record): void
    {
        $directory = dirname($this->path);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException("Cannot create log directory: {$directory}");
        }

        try {
            $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Cannot encode log record as JSON.', previous: $exception);
        }

        if (file_put_contents($this->path, $line . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
            throw new RuntimeException("Cannot write log file: {$this->path}");
        }
    }
}
