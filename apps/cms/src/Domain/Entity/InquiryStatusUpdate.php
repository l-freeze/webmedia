<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\InquiryStatus;

final readonly class InquiryStatusUpdate
{
    public function __construct(
        public int $id,
        public InquiryStatus $status,
        public string $adminNote,
    ) {
    }
}
