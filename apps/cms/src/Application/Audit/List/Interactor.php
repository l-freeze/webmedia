<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List;

use App\Cms\Application\Audit\List\Query\IAuditLogQuery;

final readonly class Interactor
{
    public function __construct(private IAuditLogQuery $auditLogs)
    {
    }

    public function execute(Input $input): Output
    {
        $limit = max(1, min(200, $input->limit));

        return new Output(rows: $this->auditLogs->list(limit: $limit));
    }
}
