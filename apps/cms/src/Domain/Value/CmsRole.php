<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final class CmsRole
{
    public const VIEWER = 'viewer';
    public const WRITER = 'writer';
    public const EDITOR = 'editor';
    public const ADMIN = 'admin';

    private const VIEWER_RANK = 10;
    private const WRITER_RANK = 20;
    private const EDITOR_RANK = 30;
    private const ADMIN_RANK = 40;

    /** @var array<string, int> */
    private const RANKS = [
        self::VIEWER => self::VIEWER_RANK,
        self::WRITER => self::WRITER_RANK,
        self::EDITOR => self::EDITOR_RANK,
        self::ADMIN => self::ADMIN_RANK,
    ];

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::VIEWER,
            self::WRITER,
            self::EDITOR,
            self::ADMIN,
        ];
    }

    public static function isAtLeast(string $actualRole, string $requiredRole): bool
    {
        return (self::RANKS[$actualRole] ?? 0) >= (self::RANKS[$requiredRole] ?? PHP_INT_MAX);
    }
}
