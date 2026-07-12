<?php

declare(strict_types=1);

namespace Tests\Shared\Time;

use App\Shared\Time\Infrastructure\SystemClock;
use PHPUnit\Framework\TestCase;

final class SystemClockTest extends TestCase
{
    public function testReturnsCurrentTimeInUtc(): void
    {
        $now = (new SystemClock())->now();

        self::assertSame('UTC', $now->getTimezone()->getName());
    }
}
