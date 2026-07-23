<?php

declare(strict_types=1);

namespace App\Cms\Application\Role\List;

use App\Cms\Application\Role\List\Value\Role;

final readonly class Output
{
    /** @param list<Role> $roles */
    public function __construct(public array $roles)
    {
    }
}
