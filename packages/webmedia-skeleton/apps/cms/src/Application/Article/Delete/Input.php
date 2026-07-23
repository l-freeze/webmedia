<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Delete;

use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

final readonly class Input
{
    private function __construct(public int $id, public CurrentCmsUser $currentUser)
    {
    }

    public static function create(int $id, CurrentCmsUser $currentUser): self
    {
        return new self(id: $id, currentUser: $currentUser);
    }
}
