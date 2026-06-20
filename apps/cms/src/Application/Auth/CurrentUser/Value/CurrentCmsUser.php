<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\CurrentUser\Value;

final readonly class CurrentCmsUser
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
        public string $role,
    ) {
    }
}
