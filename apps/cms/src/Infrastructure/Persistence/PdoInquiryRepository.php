<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\InquiryStatusUpdate;
use App\Cms\Domain\Repository\IInquiryRepository;
use App\Shared\Database\PdoHelper;
use PDO;

final readonly class PdoInquiryRepository implements IInquiryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function updateStatus(InquiryStatusUpdate $update): bool
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE inquiries SET status = :status, admin_note = :admin_note, updated_at = :updated_at WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: [
            'id' => $update->id,
            'status' => $update->status->value,
            'admin_note' => $update->adminNote,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $stmt->rowCount() > 0;
    }
}
