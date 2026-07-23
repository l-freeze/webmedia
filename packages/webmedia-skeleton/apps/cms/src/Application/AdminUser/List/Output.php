<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\List;

use App\Cms\Application\AdminUser\List\Value\AdminUser;

final readonly class Output
{
    /** @param list<AdminUser> $users */
    public function __construct(public array $users)
    {
    }
}
