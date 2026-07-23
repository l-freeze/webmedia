<?php

declare(strict_types=1);

namespace App\Shared\Time\Application;

use DateTimeImmutable;

interface IClock
{
    public function now(): DateTimeImmutable;
}
