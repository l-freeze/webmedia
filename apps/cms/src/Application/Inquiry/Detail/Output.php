<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Detail;

use App\Cms\Application\Inquiry\Detail\Value\InquiryDetail;

final readonly class Output
{
    public function __construct(public ?InquiryDetail $inquiry)
    {
    }
}
