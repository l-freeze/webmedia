<?php

declare(strict_types=1);

namespace App\Shared\SecurityLog\Infrastructure;

use App\Shared\Logging\Infrastructure\JsonLineFileWriter;
use App\Shared\SecurityLog\Application\SecurityLoggerInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;

final readonly class JsonFileSecurityLogger implements SecurityLoggerInterface
{
    private JsonLineFileWriter $writer;

    public function __construct(string $path)
    {
        $this->writer = new JsonLineFileWriter(path: $path);
    }

    public function record(SecurityLogEvent $event): void
    {
        $this->writer->write(record: [
            'timestamp' => gmdate('c'),
            'category' => 'security',
            'event_type' => $event->eventType,
            'result' => $event->result,
            'actor_user_id' => $event->actorUserId,
            'actor_role' => $event->actorRole,
            'ip_address' => $event->ipAddress,
            'user_agent' => $event->userAgent,
            'request_id' => $event->requestId,
            'metadata' => $event->metadata,
        ]);
    }
}
