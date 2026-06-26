<?php

declare(strict_types=1);

namespace App\Front\Application\Inquiry\Create;

final readonly class InquiryInputLimits
{
    public const NAME_MAX_LENGTH = 16;
    public const SUBJECT_MAX_LENGTH = 100;
    public const MESSAGE_MAX_LENGTH = 1000;
}
