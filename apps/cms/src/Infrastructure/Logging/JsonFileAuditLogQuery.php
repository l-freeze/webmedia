<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Logging;

use App\Cms\Application\Audit\List\Query\IAuditLogQuery;
use App\Cms\Application\Audit\List\Value\AuditLog;
use JsonException;

final readonly class JsonFileAuditLogQuery implements IAuditLogQuery
{
    public function __construct(private string $path)
    {
    }

    /** @return list<AuditLog> */
    public function list(int $limit): array
    {
        if (!is_file($this->path)) {
            return [];
        }

        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return [];
        }

        $rows = [];
        foreach (array_reverse($lines) as $line) {
            $row = $this->decode(line: (string) $line);
            if ($row === null) {
                continue;
            }

            $rows[] = $row;
            if (count($rows) >= $limit) {
                break;
            }
        }

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
}
