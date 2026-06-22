<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Authorization;

use App\Cms\Application\Auth\Authorization\Value\RoleCode;

final class RoleHierarchy
{
    /** @var array<string, int> */
    private const RANKS = [
        RoleCode::VIEWER => 10,
        RoleCode::WRITER => 20,
        RoleCode::EDITOR => 30,
        RoleCode::ADMIN => 40,
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
