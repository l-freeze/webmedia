<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final class InquiryStatus
{
    public const NEW = 'new';
    public const IN_PROGRESS = 'in_progress';
    public const CLOSED = 'closed';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::NEW => '未対応',
            self::IN_PROGRESS => '対応中',
            self::CLOSED => '対応済み',
        ];
    }

    public static function isValid(string $status): bool
    {
        return array_key_exists($status, self::labels());
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }
}
