<?php

declare(strict_types=1);

namespace App\Cms\Inquiry\Infrastructure\Persistence;

use App\Cms\Inquiry\Query\ListInquiries\InquiryQueryService;
use PDO;

final readonly class PdoInquiryQueryService implements InquiryQueryService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function list(): array
    {
        return $this->pdo->query('SELECT name, email, subject, status, created_at FROM inquiries ORDER BY created_at DESC')->fetchAll();
    }
}
