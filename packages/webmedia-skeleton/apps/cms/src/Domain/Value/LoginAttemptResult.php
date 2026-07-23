<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

enum LoginAttemptResult: string
{
    case Success = 'success';
    case Failure = 'failure';
    case Blocked = 'blocked';
}
