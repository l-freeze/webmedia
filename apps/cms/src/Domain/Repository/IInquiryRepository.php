<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\InquiryStatusUpdate;

interface IInquiryRepository
{
    public function updateStatus(InquiryStatusUpdate $update): bool;
}
