<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Repository\ITaxonomyRepository;
use PDO;

final readonly class PdoTaxonomyRepository implements ITaxonomyRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function createCategory(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('INSERT INTO categories (name, slug, description, created_at, updated_at) VALUES (:name, :slug, :description, :created_at, :updated_at)');
        $stmt->execute([
            'name' => (string) ($data['name'] ?? ''),
            'slug' => (string) ($data['slug'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
