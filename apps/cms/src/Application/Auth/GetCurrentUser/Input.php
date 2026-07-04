<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\GetCurrentUser;

final readonly class Input
{
    public function __construct(public int $userId)
    {
    }
}
