<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Authorization;

final class Role
{
    public const VIEWER = 'viewer';
    public const WRITER = 'writer';
    public const EDITOR = 'editor';
    public const ADMIN = 'admin';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEWER,
            self::WRITER,
            self::EDITOR,
            self::ADMIN,
        ];
    }
}
