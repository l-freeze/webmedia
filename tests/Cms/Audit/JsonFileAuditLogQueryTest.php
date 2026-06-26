<?php

declare(strict_types=1);

namespace Tests\Cms\Audit;

use App\Cms\Application\Audit\List\Value\AuditLogSearch;
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

        $query = new JsonFileAuditLogQuery(path: $path);
        $rows = $query->list(search: new AuditLogSearch(), limit: 10, offset: 0, sortDirection: 'desc');

        self::assertCount(2, $rows);
        self::assertSame(2, $query->count(search: new AuditLogSearch()));
        self::assertSame('article.updated', $rows[0]->action);
        self::assertSame('{"slug":"second"}', $rows[0]->detail);
        self::assertSame('article.created', $rows[1]->action);
    }

    public function testFiltersSortsAndSlicesAuditLogRecords(): void
    {
        $path = $this->path();
        mkdir(dirname($path), 0775, true);
        file_put_contents($path, implode(PHP_EOL, [
            json_encode([
                'timestamp' => '2026-06-25T23:59:59+00:00',
                'action' => 'article.created',
                'resource_type' => 'article',
                'resource_id' => '1',
                'result' => 'success',
                'actor_user_id' => '2',
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'timestamp' => '2026-06-26T00:00:00+00:00',
                'action' => 'article.updated',
                'resource_type' => 'article',
                'resource_id' => '1',
                'result' => 'success',
                'actor_user_id' => '2',
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'timestamp' => '2026-06-26T01:00:00+00:00',
                'action' => 'media.deleted',
                'resource_type' => 'media',
                'resource_id' => '9',
                'result' => 'success',
                'actor_user_id' => '2',
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'timestamp' => '2026-06-26T02:00:00+00:00',
                'action' => 'article.trashed',
                'resource_type' => 'article',
                'resource_id' => '3',
                'result' => 'failure',
                'actor_user_id' => '4',
            ], JSON_THROW_ON_ERROR),
        ]) . PHP_EOL);

        $search = new AuditLogSearch(
            timestampFrom: '2026-06-26T00:00:00',
            timestampTo: '2026-06-26T23:59:59',
            result: 'success',
            action: 'article.',
            resourceType: 'article',
            resourceId: '1',
            actorUserId: '2',
        );
        $query = new JsonFileAuditLogQuery(path: $path);

        self::assertSame(1, $query->count(search: $search));

        $rows = $query->list(search: $search, limit: 1, offset: 0, sortDirection: 'asc');

        self::assertCount(1, $rows);
        self::assertSame('article.updated', $rows[0]->action);
    }

    public function testMissingFileReturnsEmptyRows(): void
    {
        $query = new JsonFileAuditLogQuery(path: $this->path());
        $rows = $query->list(search: new AuditLogSearch(), limit: 10, offset: 0, sortDirection: 'desc');

        self::assertSame([], $rows);
        self::assertSame(0, $query->count(search: new AuditLogSearch()));
    }

    private function path(): string
    {
        return sys_get_temp_dir() . '/webmedia-audit-query-' . bin2hex(random_bytes(4)) . '/audit.log';
    }
}
