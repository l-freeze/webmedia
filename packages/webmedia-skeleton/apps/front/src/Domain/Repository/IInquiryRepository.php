<?php

declare(strict_types=1);

namespace App\Front\Domain\Repository;

use App\Front\Domain\Entity\Inquiry;
use App\Front\Domain\Value\InquiryId;

interface IInquiryRepository
{
    public function save(Inquiry $inquiry): InquiryId;
}
