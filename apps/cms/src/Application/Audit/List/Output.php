<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List;

use App\Cms\Application\Audit\List\Value\AuditLog;

final readonly class Output
{
    /** @param list<AuditLog> $rows */
    public function __construct(public array $rows)
    {
    }
}
