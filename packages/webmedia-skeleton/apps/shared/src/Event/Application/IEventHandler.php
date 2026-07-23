<?php

declare(strict_types=1);

namespace App\Shared\Event\Application;

interface IEventHandler
{
    public function handles(object $event): bool;

    public function handle(object $event): void;
}
