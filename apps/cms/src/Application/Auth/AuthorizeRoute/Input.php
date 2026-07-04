<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\AuthorizeRoute;

final readonly class Input
{
    public function __construct(
        public string $actorRole,
        public string $requiredPermission,
    ) {
    }
}
