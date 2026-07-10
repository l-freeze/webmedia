<?php

declare(strict_types=1);

namespace App\Front\Domain\Repository;

use App\Front\Domain\Entity\Inquiry;

interface IInquiryRepository
{
    public function create(Inquiry $inquiry): void;
}
