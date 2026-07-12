<?php

declare(strict_types=1);

namespace Tests\Shared\Time;

use App\Shared\Time\Application\IClock;
use DateTimeImmutable;

final readonly class FrozenClock implements IClock
{
    public function __construct(private DateTimeImmutable $dateTime)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->dateTime;
    }
}
