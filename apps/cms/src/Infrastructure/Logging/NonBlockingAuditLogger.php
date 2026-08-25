<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Logging;

use App\Shared\Audit\Application\IAuditLogger;
use App\Shared\Audit\Domain\AuditEvent;
use App\Shared\Logging\Infrastructure\LogWriteException;
use Closure;

final readonly class NonBlockingAuditLogger implements IAuditLogger
{
    /** @param Closure(string): void $failureReporter */
    public function __construct(
        private IAuditLogger $logger,
        private Closure $failureReporter,
    ) {
    }

    public function record(AuditEvent $event): void
    {
        try {
            $this->logger->record(event: $event);
        } catch (LogWriteException $exception) {
            ($this->failureReporter)(sprintf(
                'Audit log persistence failed: action=%s resource_type=%s result=%s error=%s',
                $event->action,
                $event->resourceType,
                $event->result,
                $exception->getMessage(),
            ));
        }
    }
}
