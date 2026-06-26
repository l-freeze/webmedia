<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Inquiry\Update\Repository\IInquiryRepository;
use App\Shared\Database\PdoHelper;
use PDO;

final readonly class PdoInquiryRepository implements IInquiryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function updateStatus(int $id, string $status, string $adminNote): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE inquiries SET status = :status, admin_note = :admin_note, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: [
            'id' => $id,
            'status' => $status,
            'admin_note' => $adminNote,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0;
    }
}
