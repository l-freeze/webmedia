<?php

declare(strict_types=1);

namespace App\Shared\Logging\Infrastructure;

use JsonException;
use RuntimeException;

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
        if (!is_dir($directory) && !mkdir($directory, self::LOG_DIRECTORY_PERMISSIONS, true) && !is_dir($directory)) {
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
