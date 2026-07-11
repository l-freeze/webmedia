<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\GetCurrentUser;

final readonly class Input
{
    private function __construct(public int $userId)
    {
    }

    public static function create(int $userId): self
    {
        return new self(userId: $userId);
    }
}
