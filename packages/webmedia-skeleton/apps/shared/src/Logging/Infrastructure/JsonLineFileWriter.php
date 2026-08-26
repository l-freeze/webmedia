<?php

declare(strict_types=1);

namespace App\Shared\Logging\Infrastructure;

use ErrorException;
use JsonException;

final readonly class JsonLineFileWriter
{
    private const LOG_DIRECTORY_PERMISSIONS = 0775;

    public function __construct(
        private string $path,
    ) {
    }

    /** @param array<string, mixed> $record */
    public function write(array $record): void
    {
        $directory = dirname($this->path);
        $this->ensureDirectory(directory: $directory);

        try {
            $line = json_encode(
                $record,
                JSON_UNESCAPED_UNICODE
                    | JSON_UNESCAPED_SLASHES
                    | JSON_INVALID_UTF8_SUBSTITUTE
                    | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new LogWriteException('Cannot encode log record as JSON.', previous: $exception);
        }

        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line,
        ): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $bytesWritten = file_put_contents($this->path, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        } catch (ErrorException $exception) {
            throw new LogWriteException(
                "Cannot write log file: {$this->path}: {$exception->getMessage()}",
                previous: $exception,
            );
        } finally {
            restore_error_handler();
        }

        if ($bytesWritten === false) {
            throw new LogWriteException("Cannot write log file: {$this->path}");
        }
    }

    private function ensureDirectory(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line,
        ): never {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        try {
            $created = mkdir($directory, self::LOG_DIRECTORY_PERMISSIONS, true);
        } catch (ErrorException $exception) {
            if (is_dir($directory)) {
                return;
            }

            throw new LogWriteException(
                "Cannot create log directory: {$directory}: {$exception->getMessage()}",
                previous: $exception,
            );
        } finally {
            restore_error_handler();
        }

        if (!$created && !is_dir($directory)) {
            throw new LogWriteException("Cannot create log directory: {$directory}");
        }
    }
}
