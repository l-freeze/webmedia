<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Front\Domain\Entity\Inquiry;
use App\Front\Domain\Value\InquiryId;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use App\Front\Domain\Repository\IInquiryRepository;
use PDO;

final readonly class PdoInquiryRepository implements IInquiryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(Inquiry $inquiry): InquiryId
    {
        $now = date('Y-m-d H:i:s');
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: "INSERT INTO inquiries (name, email, subject, message, status, created_at, updated_at) VALUES (:name, :email, :subject, :message, 'new', :created_at, :updated_at) RETURNING id");
        PdoHelper::execute(statement: $stmt, params: [
            'name' => $inquiry->name->value,
            'email' => $inquiry->email->value,
            'subject' => $inquiry->subject->value,
            'message' => $inquiry->message->value,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $inquiryId = PdoRow::from(values: ['id' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'id');

        return new InquiryId(value: $inquiryId);
    }
}
