<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Delete;

use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

final readonly class Input
{
    public function __construct(public int $id, public CurrentCmsUser $currentUser)
    {
    }
}
