<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

final class BusinessCategoryScope
{
    /** @var list<string> */
    public const SLUGS = [
        'business-system-design',
        'development-ordering',
        'service-process',
        'pera-framework',
    ];

    public static function condition(string $categoryAlias): string
    {
        $quoted = array_map(static fn (string $slug): string => "'" . $slug . "'", self::SLUGS);

        return $categoryAlias . '.slug IN (' . implode(', ', $quoted) . ')';
    }
}
