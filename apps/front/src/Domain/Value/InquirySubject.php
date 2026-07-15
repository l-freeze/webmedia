<?php

declare(strict_types=1);

namespace App\Front\Domain\Value;

final readonly class InquirySubject
{
    public function __construct(public string $value)
    {
    }
}
