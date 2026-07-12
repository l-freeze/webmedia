<?php

declare(strict_types=1);

namespace App\Shared\SecurityLog\Application;

use App\Shared\SecurityLog\Domain\SecurityLogEvent;

interface ISecurityLogger
{
    public function record(SecurityLogEvent $event): void;
}
