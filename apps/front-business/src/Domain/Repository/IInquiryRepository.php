<?php

declare(strict_types=1);

namespace App\FrontBusiness\Domain\Repository;

use App\FrontBusiness\Domain\Entity\Inquiry;

interface IInquiryRepository
{
    public function create(Inquiry $inquiry): void;
}
