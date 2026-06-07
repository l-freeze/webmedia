<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Inquiry\List\Query\IInquiryQuery;
use App\Cms\Application\Inquiry\List\Value\Inquiry;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoInquiryQuery implements IInquiryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Inquiry> */
    public function list(): array
    {
        $inquiries = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT name, email, subject, status, created_at FROM inquiries ORDER BY created_at DESC')) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $inquiries[] = new Inquiry(
                name: $pdoRow->string(key: 'name'),
                email: $pdoRow->string(key: 'email'),
                subject: $pdoRow->string(key: 'subject'),
                status: $pdoRow->string(key: 'status'),
                createdAt: $pdoRow->string(key: 'created_at'),
            );
        }

        return $inquiries;
    }
}
