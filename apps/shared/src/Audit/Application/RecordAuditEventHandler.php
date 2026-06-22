<?php

declare(strict_types=1);

namespace App\Shared\Audit\Application;

use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Event\Application\EventHandlerInterface;

final readonly class RecordAuditEventHandler implements EventHandlerInterface
{
    public function __construct(private AuditLoggerInterface $logger)
    {
    }

    public function handles(object $event): bool
    {
        return $event instanceof AuditEvent;
    }

    public function handle(object $event): void
    {
        if (!$event instanceof AuditEvent) {
            return;
        }

        $this->logger->record(event: $event);
    }
}
