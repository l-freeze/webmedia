<?php

declare(strict_types=1);

namespace App\Shared\Time\Infrastructure;

use App\Shared\Time\Application\IClock;
use DateTimeImmutable;
use DateTimeZone;

final readonly class SystemClock implements IClock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable(datetime: 'now', timezone: new DateTimeZone('UTC'));
    }
}
