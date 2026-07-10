<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\FrontBusiness\Domain\Entity\Inquiry;
use App\Shared\Database\PdoHelper;
use App\FrontBusiness\Domain\Repository\IInquiryRepository;
use PDO;

final readonly class PdoInquiryRepository implements IInquiryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(Inquiry $inquiry): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: "INSERT INTO inquiries (name, email, subject, message, status, created_at, updated_at) VALUES (:name, :email, :subject, :message, 'new', :created_at, :updated_at)");
        PdoHelper::execute(statement: $stmt, params: [
            'name' => $inquiry->name,
            'email' => $inquiry->email,
            'subject' => $inquiry->subject,
            'message' => $inquiry->message,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
