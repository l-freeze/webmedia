<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Repository\IPageRepository;
use PDO;

final readonly class PdoPageRepository implements IPageRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('INSERT INTO pages (title, slug, body_markdown, status, created_at, updated_at) VALUES (:title, :slug, :body, "draft", :created_at, :updated_at)');
        $stmt->execute([
            'title' => (string) ($data['title'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'body' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
