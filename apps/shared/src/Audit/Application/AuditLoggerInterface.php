<?php

declare(strict_types=1);

namespace App\Shared\Audit\Application;

use App\Shared\Audit\Domain\AuditEvent;

interface AuditLoggerInterface
{
    public function record(AuditEvent $event): void;
}
