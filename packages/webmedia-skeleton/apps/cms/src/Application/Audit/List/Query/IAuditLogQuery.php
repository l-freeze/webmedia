<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List\Query;

use App\Cms\Application\Audit\List\Value\AuditLog;
use App\Cms\Application\Audit\List\Value\AuditLogSearch;

interface IAuditLogQuery
{
    /** @return list<AuditLog> */
    public function list(AuditLogSearch $search, int $limit, int $offset, string $sortDirection): array;

    public function count(AuditLogSearch $search): int;
}
