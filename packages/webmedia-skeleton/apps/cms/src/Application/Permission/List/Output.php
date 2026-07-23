<?php

declare(strict_types=1);

namespace App\Cms\Application\Permission\List;

use App\Cms\Application\Permission\List\Value\Permission;

final readonly class Output
{
    /** @param list<Permission> $permissions */
    public function __construct(public array $permissions)
    {
    }
}
