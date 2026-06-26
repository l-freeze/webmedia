<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Inquiry\Detail;
use App\Cms\Application\Inquiry\List\Query\IInquiryQuery;
use App\Cms\Application\Inquiry\List\Value\Inquiry;
use App\Cms\Application\Inquiry\Value\InquiryStatus;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoInquiryQuery implements IInquiryQuery, Detail\Query\IInquiryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Inquiry> */
    public function list(int $limit, int $offset): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, name, email, subject, status, created_at FROM inquiries ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        PdoHelper::execute(statement: $stmt);

        $inquiries = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $inquiries[] = new Inquiry(
                id: $pdoRow->int(key: 'id'),
                name: $pdoRow->string(key: 'name'),
                email: $pdoRow->string(key: 'email'),
                subject: $pdoRow->string(key: 'subject'),
                status: $pdoRow->string(key: 'status'),
                statusLabel: InquiryStatus::label(status: $pdoRow->string(key: 'status')),
                createdAt: $pdoRow->string(key: 'created_at'),
            );
        }

        return $inquiries;
    }

    public function count(): int
    {
        $value = PdoHelper::fetchColumn(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT COUNT(*) FROM inquiries'));

        return is_numeric($value) ? (int) $value : 0;
    }

    public function find(int $id): ?Detail\Value\InquiryDetail
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, name, email, subject, message, status, admin_note, created_at, updated_at FROM inquiries WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        $pdoRow = PdoRow::from(values: $row);

        return new Detail\Value\InquiryDetail(
            id: $pdoRow->int(key: 'id'),
            name: $pdoRow->string(key: 'name'),
            email: $pdoRow->string(key: 'email'),
            subject: $pdoRow->string(key: 'subject'),
            message: $pdoRow->string(key: 'message'),
            status: $pdoRow->string(key: 'status'),
            statusLabel: InquiryStatus::label(status: $pdoRow->string(key: 'status')),
            adminNote: $pdoRow->string(key: 'admin_note'),
            createdAt: $pdoRow->string(key: 'created_at'),
            updatedAt: $pdoRow->string(key: 'updated_at'),
        );
    }
}
