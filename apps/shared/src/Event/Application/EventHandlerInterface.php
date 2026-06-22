<?php

declare(strict_types=1);

namespace App\Shared\Event\Application;

interface EventHandlerInterface
{
    public function handles(object $event): bool;

    public function handle(object $event): void;
}
