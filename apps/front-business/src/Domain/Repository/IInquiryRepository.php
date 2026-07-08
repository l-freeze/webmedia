<?php

declare(strict_types=1);

namespace App\FrontBusiness\Domain\Repository;

interface IInquiryRepository
{
    public function create(string $name, string $email, string $subject, string $message): void;
}
