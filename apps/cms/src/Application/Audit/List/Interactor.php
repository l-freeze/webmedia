<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List;

use App\Cms\Application\Audit\List\Query\IAuditLogQuery;

final readonly class Interactor
{
    private const FIRST_PAGE = 1;
    private const MIN_LIMIT = 1;
    private const MAX_LIMIT = 200;

    public function __construct(private IAuditLogQuery $auditLogs)
    {
    }

    public function execute(Input $input): Output
    {
        $perPage = max(self::MIN_LIMIT, min(self::MAX_LIMIT, $input->limit));
        $totalItems = $this->auditLogs->count($input->search);
        $totalPages = max(self::FIRST_PAGE, (int) ceil($totalItems / $perPage));
        $currentPage = min(max(self::FIRST_PAGE, $input->page), $totalPages);
        $offset = ($currentPage - self::FIRST_PAGE) * $perPage;
        $sortDirection = $input->sortDirection === 'asc' ? 'asc' : 'desc';
        $rows = $this->auditLogs->list(
            search: $input->search,
            limit: $perPage,
            offset: $offset,
            sortDirection: $sortDirection,
        );

        return new Output(
            rows: $rows,
            currentPage: $currentPage,
            perPage: $perPage,
            totalItems: $totalItems,
            totalPages: $totalPages,
        );
    }
}
