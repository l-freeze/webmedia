<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

use DateTimeImmutable;

final readonly class LoginAttemptResetAt
{
    public function __construct(public DateTimeImmutable $value)
    {
    }
}
