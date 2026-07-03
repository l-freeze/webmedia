<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Login\Query;

use App\Cms\Application\Auth\Login\Value\LoginAttemptFailureCounts;
use DateTimeImmutable;

interface ILoginAttemptQuery
{
    public function failureCounts(string $email, string $ipAddress, DateTimeImmutable $since): LoginAttemptFailureCounts;
}
