<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

interface IInquiryRepository
{
    public function updateStatus(int $id, string $status, string $adminNote): bool;
}
