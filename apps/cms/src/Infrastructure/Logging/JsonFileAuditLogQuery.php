<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Logging;

use App\Cms\Application\Audit\List\Query\IAuditLogQuery;
use App\Cms\Application\Audit\List\Value\AuditLog;
use App\Cms\Application\Audit\List\Value\AuditLogSearch;
use JsonException;

final readonly class JsonFileAuditLogQuery implements IAuditLogQuery
{
    public function __construct(private string $path)
    {
    }

    /** @return list<AuditLog> */
    public function list(AuditLogSearch $search, int $limit, int $offset, string $sortDirection): array
    {
        $rows = $this->filteredRows(search: $search, sortDirection: $sortDirection);

        return array_slice($rows, max(0, $offset), max(1, $limit));
    }

    public function count(AuditLogSearch $search): int
    {
        return count($this->filteredRows(search: $search, sortDirection: 'desc'));
    }

    /** @return list<AuditLog> */
    private function filteredRows(AuditLogSearch $search, string $sortDirection): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $rows = [];
        foreach ($lines as $line) {
            $row = $this->decode(line: (string) $line);
            if ($row === null) {
                continue;
            }

            if ($this->matches(row: $row, search: $search)) {
                $rows[] = $row;
            }
        }

        usort($rows, static function (AuditLog $left, AuditLog $right) use ($sortDirection): int {
            $compared = strcmp($left->timestamp, $right->timestamp);

            return $sortDirection === 'asc' ? $compared : -$compared;
        });

        return $rows;
    }

    private function decode(string $line): ?AuditLog
    {
        try {
            $record = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (!is_array($record)) {
            return null;
        }

        return new AuditLog(
            timestamp: $this->string(record: $record, key: 'timestamp'),
            action: $this->string(record: $record, key: 'action'),
            resourceType: $this->string(record: $record, key: 'resource_type'),
            resourceId: $this->string(record: $record, key: 'resource_id'),
            result: $this->string(record: $record, key: 'result'),
            actorUserId: $this->string(record: $record, key: 'actor_user_id'),
            detail: $this->detail(record: $record),
        );
    }

    /** @param array<mixed> $record */
    private function string(array $record, string $key): string
    {
        $value = $record[$key] ?? '';

        return is_scalar($value) ? (string) $value : '';
    }

    /** @param array<mixed> $record */
    private function detail(array $record): string
    {
        $metadata = $record['metadata'] ?? [];
        if (!is_array($metadata) || $metadata === []) {
            return '';
        }

        try {
            return json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '';
        }
    }

    private function matches(AuditLog $row, AuditLogSearch $search): bool
    {
        if ($search->timestampFrom !== '' && strcmp($row->timestamp, $search->timestampFrom) < 0) {
            return false;
        }

        if ($search->timestampTo !== '' && strcmp($row->timestamp, $search->timestampTo) > 0) {
            return false;
        }

        return $this->contains(haystack: $row->result, needle: $search->result)
            && $this->contains(haystack: $row->action, needle: $search->action)
            && $this->contains(haystack: $row->resourceType, needle: $search->resourceType)
            && $this->contains(haystack: $row->resourceId, needle: $search->resourceId)
            && $this->contains(haystack: $row->actorUserId, needle: $search->actorUserId);
    }

    private function contains(string $haystack, string $needle): bool
    {
        return $needle === '' || str_contains($haystack, $needle);
    }
}
