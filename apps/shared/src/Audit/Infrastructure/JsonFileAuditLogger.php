<?php

declare(strict_types=1);

namespace App\Shared\Audit\Infrastructure;

use App\Shared\Audit\Application\IAuditLogger;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Logging\Infrastructure\JsonLineFileWriter;

final readonly class JsonFileAuditLogger implements IAuditLogger
{
    private JsonLineFileWriter $writer;

    public function __construct(string $path)
    {
        $this->writer = new JsonLineFileWriter(path: $path);
    }

    public function record(AuditEvent $event): void
    {
        $this->writer->write(record: [
            'timestamp' => gmdate('c'),
            'category' => 'audit',
            'action' => $event->action,
            'resource_type' => $event->resourceType,
            'resource_id' => $event->resourceId,
            'result' => $event->result,
            'actor_user_id' => $event->actorUserId,
            'actor_role' => $event->actorRole,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'request_id' => $event->requestId,
            'before' => $event->before,
            'after' => $event->after,
            'metadata' => $event->metadata,
        ]);
    }
}
