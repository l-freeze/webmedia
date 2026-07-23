<?php

declare(strict_types=1);

namespace App\Cms\Application\Role\List\Value;

final readonly class Permission
{
    public function __construct(public string $code)
    {
    }
}
