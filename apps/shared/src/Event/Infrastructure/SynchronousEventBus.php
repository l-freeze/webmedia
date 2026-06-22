<?php

declare(strict_types=1);

namespace App\Shared\Event\Infrastructure;

use App\Shared\Event\Application\EventBusInterface;
use App\Shared\Event\Application\EventHandlerInterface;

final readonly class SynchronousEventBus implements EventBusInterface
{
    /**
     * @param list<EventHandlerInterface> $handlers
     */
    public function __construct(private array $handlers)
    {
    }

    public function dispatch(object $event): void
    {
        foreach ($this->handlers as $handler) {
            if (!$handler->handles(event: $event)) {
                continue;
            }

            $handler->handle(event: $event);
        }
    }
}
