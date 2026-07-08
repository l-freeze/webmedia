<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\Shared\Database\PdoHelper;
use App\FrontBusiness\Domain\Repository\IInquiryRepository;
use PDO;

final readonly class PdoInquiryRepository implements IInquiryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $name, string $email, string $subject, string $message): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: "INSERT INTO inquiries (name, email, subject, message, status, created_at, updated_at) VALUES (:name, :email, :subject, :message, 'new', :created_at, :updated_at)");
        PdoHelper::execute(statement: $stmt, params: compact('name', 'email', 'subject', 'message') + ['created_at' => $now, 'updated_at' => $now]);
    }
}
