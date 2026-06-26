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
        $perPage = max(1, min(200, $input->limit));
        $totalItems = $this->auditLogs->count(search: $input->search);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $currentPage = min(max(1, $input->page), $totalPages);
        $offset = ($currentPage - 1) * $perPage;
        $sortDirection = $input->sortDirection === 'asc' ? 'asc' : 'desc';

        return new Output(
            rows: $this->auditLogs->list(
                search: $input->search,
                limit: $perPage,
                offset: $offset,
                sortDirection: $sortDirection,
            ),
            currentPage: $currentPage,
            perPage: $perPage,
            totalItems: $totalItems,
            totalPages: $totalPages,
        );
    }
}
