<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Authorization;

use App\Cms\Application\Auth\Authorization\Value\RoleCode;

final class RoleHierarchy
{
    private const VIEWER_RANK = 10;
    private const WRITER_RANK = 20;
    private const EDITOR_RANK = 30;
    private const ADMIN_RANK = 40;

    /** @var array<string, int> */
    private const RANKS = [
        RoleCode::VIEWER => self::VIEWER_RANK,
        RoleCode::WRITER => self::WRITER_RANK,
        RoleCode::EDITOR => self::EDITOR_RANK,
        RoleCode::ADMIN => self::ADMIN_RANK,
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
