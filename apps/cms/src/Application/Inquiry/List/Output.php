<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\List;

use App\Cms\Application\Inquiry\List\Value\Inquiry;

final readonly class Output
{
    /** @param array<int, Inquiry> $rows */
    public function __construct(
        public array $rows,
        public int $currentPage,
        public int $perPage,
        public int $totalItems,
        public int $totalPages,
    ) {
    }

    public function previousPage(): ?int
    {
        return $this->currentPage > 1 ? $this->currentPage - 1 : null;
    }

    public function nextPage(): ?int
    {
        return $this->currentPage < $this->totalPages ? $this->currentPage + 1 : null;
    }
}
