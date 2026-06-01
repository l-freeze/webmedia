<?php

declare(strict_types=1);

namespace App\Shared\Audit\Infrastructure;

use App\Shared\Audit\Application\AuditLoggerInterface;
use App\Shared\Audit\Domain\AuditEvent;
use LogicException;

final readonly class PlaceholderAuditLogger implements AuditLoggerInterface
{
    public function record(AuditEvent $event): void
    {
        throw new LogicException('Audit logger is not configured. Bind AuditLoggerInterface to a real audit logger before recording audit events.');
    }
}
