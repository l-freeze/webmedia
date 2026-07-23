<?php

declare(strict_types=1);

namespace App\Front\Domain\Value;

final readonly class InquiryMessage
{
    public function __construct(public string $value)
    {
    }
}
