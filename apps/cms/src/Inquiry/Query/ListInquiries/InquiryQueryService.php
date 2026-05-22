<?php

declare(strict_types=1);

namespace App\Cms\Inquiry\Query\ListInquiries;

interface InquiryQueryService
{
    /** @return array<int, array<string, mixed>> */
    public function list(): array;
}
