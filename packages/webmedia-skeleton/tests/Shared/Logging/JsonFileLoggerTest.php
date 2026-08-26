<?php

declare(strict_types=1);

namespace Tests\Shared\Logging;

use App\Shared\Logging\Infrastructure\JsonFileLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class JsonFileLoggerTest extends TestCase
{
    public function testWritesJsonLineLog(): void
    {
        $path = sys_get_temp_dir() . '/webmedia-json-log-' . bin2hex(random_bytes(4)) . '/app.log';
        $logger = new JsonFileLogger(path: $path);

        $logger->info('article saved', ['articleId' => 10]);

        $record = $this->record(path: $path);
        $context = $record['context'] ?? null;
        self::assertIsArray($context);

        self::assertSame('INFO', $record['severity']);
        self::assertSame('article saved', $record['message']);
        self::assertSame(10, $context['articleId']);
    }

    public function testNormalizesThrowableContext(): void
    {
        $path = sys_get_temp_dir() . '/webmedia-json-log-' . bin2hex(random_bytes(4)) . '/app.log';
        $logger = new JsonFileLogger(path: $path);

        $logger->error('failed', ['exception' => new RuntimeException('broken')]);

        $record = $this->record(path: $path);
        $context = $record['context'] ?? null;
        self::assertIsArray($context);
        $exception = $context['exception'] ?? null;
        self::assertIsArray($exception);

        self::assertSame(RuntimeException::class, $exception['class']);
        self::assertSame('broken', $exception['message']);
    }

    public function testSubstitutesInvalidUtf8AndKeepsJsonLineParseable(): void
    {
        $path = sys_get_temp_dir() . '/webmedia-json-log-' . bin2hex(random_bytes(4)) . '/app.log';
        $logger = new JsonFileLogger(path: $path);

        $logger->info("\xFF", ['invalid' => "\xFF"]);

        $record = $this->record(path: $path);
        $context = $record['context'] ?? null;
        self::assertIsArray($context);
        self::assertSame("\u{FFFD}", $record['message']);
        self::assertSame("\u{FFFD}", $context['invalid']);
    }

    /** @return array<string, mixed> */
    private function record(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        self::assertIsArray($lines);

        $record = json_decode((string) ($lines[0] ?? ''), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($record);

        $typedRecord = [];
        foreach ($record as $key => $value) {
            self::assertIsString($key);
            $typedRecord[$key] = $value;
        }

        return $typedRecord;
    }
}
