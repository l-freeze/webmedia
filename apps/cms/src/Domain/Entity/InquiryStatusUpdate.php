<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

final readonly class InquiryStatusUpdate
{
    public function __construct(
        public int $id,
        public string $status,
        public string $adminNote,
    ) {
    }
}
