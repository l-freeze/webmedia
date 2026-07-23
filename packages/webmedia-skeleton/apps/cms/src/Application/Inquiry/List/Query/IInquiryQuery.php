<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\List\Query;

use App\Cms\Application\Inquiry\List\Value\Inquiry;

interface IInquiryQuery
{
    /** @return array<int, Inquiry> */
    public function list(int $limit, int $offset): array;

    public function count(): int;
}
