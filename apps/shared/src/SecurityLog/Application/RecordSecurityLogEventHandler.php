<?php

declare(strict_types=1);

namespace App\Shared\SecurityLog\Application;

use App\Shared\Event\Application\EventHandlerInterface;
use App\Shared\SecurityLog\Domain\SecurityLogEvent;

final readonly class RecordSecurityLogEventHandler implements EventHandlerInterface
{
    public function __construct(private SecurityLoggerInterface $logger)
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
