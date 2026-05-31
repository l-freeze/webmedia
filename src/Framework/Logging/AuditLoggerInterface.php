<?php

declare(strict_types=1);

namespace App\Framework\Logging;

interface AuditLoggerInterface
{
    public function record(AuditEvent $event): void;
}
