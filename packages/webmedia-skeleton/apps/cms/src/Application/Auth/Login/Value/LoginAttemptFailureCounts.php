<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login\Value;

final readonly class LoginAttemptFailureCounts
{
    public function __construct(public int $byEmailAndIp, public int $byIp)
    {
    }
}
