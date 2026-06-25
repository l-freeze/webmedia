<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Detail\Value;

final readonly class InquiryDetail
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $subject,
        public string $message,
        public string $status,
        public string $adminNote,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }
}
