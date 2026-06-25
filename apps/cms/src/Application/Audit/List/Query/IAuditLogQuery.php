<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List\Query;

use App\Cms\Application\Audit\List\Value\AuditLog;

interface IAuditLogQuery
{
    /** @return list<AuditLog> */
    public function list(int $limit): array;
}
