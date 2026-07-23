<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\InquiryAdminNote;
use App\Cms\Domain\Value\InquiryId;
use App\Cms\Domain\Value\InquiryStatus;

final readonly class InquiryStatusUpdate
{
    public function __construct(
        public InquiryId $id,
        public InquiryStatus $status,
        public InquiryAdminNote $adminNote,
    ) {
    }
}
