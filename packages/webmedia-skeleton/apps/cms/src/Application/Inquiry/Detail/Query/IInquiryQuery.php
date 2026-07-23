<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Detail\Query;

use App\Cms\Application\Inquiry\Detail\Value\InquiryDetail;

interface IInquiryQuery
{
    public function find(int $id): ?InquiryDetail;
}
