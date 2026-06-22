<?php

declare(strict_types=1);

namespace App\Shared\Event\Application;

interface EventBusInterface
{
    public function dispatch(object $event): void;
}
