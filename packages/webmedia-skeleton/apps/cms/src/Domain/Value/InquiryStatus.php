<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

enum InquiryStatus: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Closed = 'closed';

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::New->value => '未対応',
            self::InProgress->value => '対応中',
            self::Closed->value => '対応済み',
        ];
    }

    public static function isValid(string $status): bool
    {
        return self::tryFrom($status) !== null;
    }

    public static function label(string $status): string
    {
        return self::labels()[$status] ?? $status;
    }
}
