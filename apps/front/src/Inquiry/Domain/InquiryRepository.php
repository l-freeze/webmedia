<?php

declare(strict_types=1);

namespace App\Front\Inquiry\Domain;

interface InquiryRepository
{
    public function create(string $name, string $email, string $subject, string $message): void;
}
