<?php

declare(strict_types=1);

namespace Tests\Cms\Audit;

use App\Cms\Infrastructure\Logging\JsonFileAuditLogQuery;
use PHPUnit\Framework\TestCase;

final class JsonFileAuditLogQueryTest extends TestCase
{
    public function testListsAuditLogRecordsNewestFirst(): void
    {
        $path = $this->path();
        mkdir(dirname($path), 0775, true);
        file_put_contents($path, implode(PHP_EOL, [
            json_encode([
                'timestamp' => '2026-06-26T00:00:00+00:00',
                'category' => 'audit',
                'action' => 'article.created',
                'resource_type' => 'article',
                'resource_id' => '1',
                'result' => 'success',
                'actor_user_id' => '2',
                'metadata' => ['slug' => 'first'],
            ], JSON_THROW_ON_ERROR),
            '{invalid-json',
            json_encode([
                'timestamp' => '2026-06-26T01:00:00+00:00',
                'category' => 'audit',
                'action' => 'article.updated',
                'resource_type' => 'article',
                'resource_id' => '1',
                'result' => 'success',
                'actor_user_id' => '2',
                'metadata' => ['slug' => 'second'],
            ], JSON_THROW_ON_ERROR),
        ]) . PHP_EOL);

        $rows = (new JsonFileAuditLogQuery(path: $path))->list(limit: 10);

        self::assertCount(2, $rows);
        self::assertSame('article.updated', $rows[0]->action);
        self::assertSame('{"slug":"second"}', $rows[0]->detail);
        self::assertSame('article.created', $rows[1]->action);
    }

    public function testMissingFileReturnsEmptyRows(): void
    {
        $rows = (new JsonFileAuditLogQuery(path: $this->path()))->list(limit: 10);

        self::assertSame([], $rows);
    }

    private function path(): string
    {
        return sys_get_temp_dir() . '/webmedia-audit-query-' . bin2hex(random_bytes(4)) . '/audit.log';
    }
}
