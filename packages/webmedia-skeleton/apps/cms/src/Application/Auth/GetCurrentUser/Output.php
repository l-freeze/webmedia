<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\GetCurrentUser;

use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

final readonly class Output
{
    public function __construct(public ?CurrentCmsUser $user)
    {
    }
}
