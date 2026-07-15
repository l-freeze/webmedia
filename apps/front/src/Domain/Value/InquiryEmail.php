<?php

declare(strict_types=1);

namespace App\Front\Domain\Value;

final readonly class InquiryEmail
{
    public function __construct(public string $value)
    {
    }
}
