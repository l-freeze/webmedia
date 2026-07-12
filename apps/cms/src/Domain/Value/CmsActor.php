<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class CmsActor
{
    public function __construct(
        public int $id,
        public CmsRole $role,
    ) {
    }
}
