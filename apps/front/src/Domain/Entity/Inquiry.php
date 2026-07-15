<?php

declare(strict_types=1);

namespace App\Front\Domain\Entity;

use App\Front\Domain\Value\InquiryEmail;
use App\Front\Domain\Value\InquiryId;
use App\Front\Domain\Value\InquiryMessage;
use App\Front\Domain\Value\InquiryName;
use App\Front\Domain\Value\InquirySubject;

final readonly class Inquiry
{
    public function __construct(
        public ?InquiryId $id,
        public InquiryName $name,
        public InquiryEmail $email,
        public InquirySubject $subject,
        public InquiryMessage $message,
    ) {
    }
}
