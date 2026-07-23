<?php

declare(strict_types=1);

namespace App\Shared\SecurityLog\Application;

use App\Shared\Event\Application\IEventHandler;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;

final readonly class RecordSecurityLogEventHandler implements IEventHandler
{
    public function __construct(private ISecurityLogger $logger)
    {
    }

    public function handles(object $event): bool
    {
        return $event instanceof SecurityLogEvent;
    }

    public function handle(object $event): void
    {
        if (!$event instanceof SecurityLogEvent) {
            return;
        }

        $this->logger->record(event: $event);
    }
}
