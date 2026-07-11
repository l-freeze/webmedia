<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List;

use App\Cms\Application\Audit\List\Value\AuditLogSearch;

final readonly class Input
{
    public const DEFAULT_LIMIT = 50;

    private function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public string $sortDirection = 'desc',
        public AuditLogSearch $search = new AuditLogSearch(),
    ) {
    }

    public static function create(
        int $page = 1,
        int $limit = self::DEFAULT_LIMIT,
        string $sortDirection = 'desc',
        AuditLogSearch $search = new AuditLogSearch(),
    ): self {
        return new self(page: max(1, $page), limit: $limit, sortDirection: trim($sortDirection), search: $search);
    }
}
