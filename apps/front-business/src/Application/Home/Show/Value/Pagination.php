<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Home\Show\Value;

final readonly class Pagination
{
    public int $totalPages;
    public ?int $previousPage;
    public ?int $nextPage;

    public function __construct(
        public int $currentPage,
        public int $perPage,
        public int $totalItems,
    ) {
        $this->totalPages = max(1, (int) ceil($this->totalItems / $this->perPage));
        $this->previousPage = $this->currentPage > 1 ? $this->currentPage - 1 : null;
        $this->nextPage = $this->currentPage < $this->totalPages ? $this->currentPage + 1 : null;
    }

    /** @return array<string, int|null> */
    public function toArray(): array
    {
        return [
            'current_page' => $this->currentPage,
            'per_page' => $this->perPage,
            'total_items' => $this->totalItems,
            'total_pages' => $this->totalPages,
            'previous_page' => $this->previousPage,
            'next_page' => $this->nextPage,
        ];
    }
}
