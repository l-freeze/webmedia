<?php

declare(strict_types=1);

namespace App\Shared\Event\Application;

interface IEventBus
{
    public function dispatch(object $event): void;
}
