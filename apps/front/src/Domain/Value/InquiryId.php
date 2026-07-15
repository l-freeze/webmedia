<?php

declare(strict_types=1);

namespace App\Front\Domain\Value;

final readonly class InquiryId
{
    public function __construct(public int $value)
    {
    }
}
