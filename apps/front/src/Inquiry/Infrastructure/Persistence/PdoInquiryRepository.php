<?php

declare(strict_types=1);

namespace App\Front\Inquiry\Infrastructure\Persistence;

use App\Front\Inquiry\Domain\InquiryRepository;
use PDO;

final readonly class PdoInquiryRepository implements InquiryRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(string $name, string $email, string $subject, string $message): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('INSERT INTO inquiries (name, email, subject, message, status, created_at, updated_at) VALUES (:name, :email, :subject, :message, "new", :created_at, :updated_at)');
        $stmt->execute(compact('name', 'email', 'subject', 'message') + ['created_at' => $now, 'updated_at' => $now]);
    }
}
