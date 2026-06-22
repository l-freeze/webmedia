<?php

declare(strict_types=1);

namespace Tests\Shared\Logging;

use App\Shared\ApplicationLog\Infrastructure\JsonFileApplicationLogger;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Audit\Infrastructure\JsonFileAuditLogger;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;
use App\Shared\SecurityLog\Infrastructure\JsonFileSecurityLogger;
use PHPUnit\Framework\TestCase;

final class SeparatedLoggersTest extends TestCase
{
    public function testApplicationLoggerWritesOperationalRecord(): void
    {
        $path = $this->path(file: 'application.log');
        $logger = new JsonFileApplicationLogger(path: $path);

        $logger->info('cache warmed', ['cache_key' => 'front.home']);

        $record = $this->record(path: $path);
        self::assertSame('INFO', $record['severity']);
        self::assertSame('cache warmed', $record['message']);
    }

    public function testAuditLoggerWritesBusinessTraceRecord(): void
    {
        $path = $this->path(file: 'audit.log');
        $logger = new JsonFileAuditLogger(path: $path);

        $logger->record(event: AuditEvent::success(
            action: 'article.updated',
            resourceType: 'article',
            resourceId: '10',
            actorUserId: '2',
            metadata: ['status' => 'draft'],
        ));

        $record = $this->record(path: $path);
        self::assertSame('audit', $record['category']);
        self::assertSame('article.updated', $record['action']);
        self::assertSame('article', $record['resource_type']);
        self::assertSame('10', $record['resource_id']);
        self::assertSame('2', $record['actor_user_id']);
    }

    public function testSecurityLoggerWritesAuthSecurityRecord(): void
    {
        $path = $this->path(file: 'security.log');
        $logger = new JsonFileSecurityLogger(path: $path);

        $logger->record(event: new SecurityLogEvent(
            eventType: 'cms.login',
            result: 'failure',
            ipAddress: '127.0.0.1',
            userAgent: 'Test Agent',
            metadata: ['reason' => 'invalid_credentials'],
        ));

        $record = $this->record(path: $path);
        self::assertSame('security', $record['category']);
        self::assertSame('cms.login', $record['event_type']);
        self::assertSame('failure', $record['result']);
        self::assertSame('127.0.0.1', $record['ip_address']);
    }

    private function path(string $file): string
    {
        return sys_get_temp_dir() . '/webmedia-separated-log-' . bin2hex(random_bytes(4)) . '/' . $file;
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
