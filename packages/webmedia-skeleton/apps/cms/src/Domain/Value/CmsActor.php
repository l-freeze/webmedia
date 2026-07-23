<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class CmsActor
{
    public function __construct(
        public AdminUserId $id,
        public CmsRole $role,
    ) {
    }
}
