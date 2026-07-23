<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\AuthorizeRoute;

final readonly class Input
{
    private function __construct(
        public string $actorRole,
        public string $requiredPermission,
    ) {
    }

    public static function create(string $actorRole, string $requiredPermission): self
    {
        return new self(actorRole: trim($actorRole), requiredPermission: trim($requiredPermission));
    }
}
