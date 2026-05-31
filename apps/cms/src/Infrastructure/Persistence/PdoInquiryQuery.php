<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Inquiry\List\Query\IInquiryQuery;
use App\Cms\Application\Inquiry\List\Value\Inquiry;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoInquiryQuery implements IInquiryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Inquiry> */
    public function list(): array
    {
        return array_map(fn (array $row): Inquiry => new Inquiry(
            name: (string) $row['name'],
            email: (string) $row['email'],
            subject: (string) $row['subject'],
            status: (string) $row['status'],
            createdAt: (string) $row['created_at'],
        ), PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT name, email, subject, status, created_at FROM inquiries ORDER BY created_at DESC')));
    }
}
