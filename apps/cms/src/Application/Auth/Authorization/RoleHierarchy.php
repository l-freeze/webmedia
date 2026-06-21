<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Authorization;

final class RoleHierarchy
{
    /** @var array<string, int> */
    private const RANKS = [
        Role::VIEWER => 10,
        Role::WRITER => 20,
        Role::EDITOR => 30,
        Role::ADMIN => 40,
    ];

    public function isAtLeast(string $actualRole, string $requiredRole): bool
    {
        $actualRank = self::RANKS[$actualRole] ?? null;
        $requiredRank = self::RANKS[$requiredRole] ?? null;

        if ($actualRank === null || $requiredRank === null) {
            return false;
        }

        return $actualRank >= $requiredRank;
    }
}
