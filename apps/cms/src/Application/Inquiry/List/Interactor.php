<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\List;

use App\Cms\Application\Inquiry\List\Query\IInquiryQuery;

final readonly class Interactor
{
    private const PER_PAGE = 100;

    public function __construct(private IInquiryQuery $inquiries)
    {
    }

    public function execute(Input $input): Output
    {
        $totalItems = $this->inquiries->count();
        $totalPages = max(1, (int) ceil($totalItems / self::PER_PAGE));
        $currentPage = min(max(1, $input->page), $totalPages);
        $offset = ($currentPage - 1) * self::PER_PAGE;
        $rows = $this->inquiries->list(limit: self::PER_PAGE, offset: $offset);

        return new Output(
            rows: $rows,
            currentPage: $currentPage,
            perPage: self::PER_PAGE,
            totalItems: $totalItems,
            totalPages: $totalPages,
        );
    }
}
