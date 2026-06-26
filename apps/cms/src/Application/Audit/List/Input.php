<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List;

use App\Cms\Application\Audit\List\Value\AuditLogSearch;

final readonly class Input
{
    public function __construct(
        public int $page = 1,
        public int $limit = 50,
        public string $sortDirection = 'desc',
        public AuditLogSearch $search = new AuditLogSearch(),
    ) {
    }
}
