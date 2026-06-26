<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List;

use App\Cms\Application\Audit\List\Value\AuditLogSearch;

final readonly class Input
{
    public const DEFAULT_LIMIT = 50;

    public function __construct(
        public int $page = 1,
        public int $limit = self::DEFAULT_LIMIT,
        public string $sortDirection = 'desc',
        public AuditLogSearch $search = new AuditLogSearch(),
    ) {
    }
}
