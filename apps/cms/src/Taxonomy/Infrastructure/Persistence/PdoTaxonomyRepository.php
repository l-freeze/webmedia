<?php

declare(strict_types=1);

namespace App\Cms\Taxonomy\Infrastructure\Persistence;

use App\Cms\Taxonomy\Domain\TaxonomyRepository;
use PDO;

final readonly class PdoTaxonomyRepository implements TaxonomyRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function categories(): array
    {
        return $this->pdo->query('SELECT name, slug, description FROM categories ORDER BY sort_order, name')->fetchAll();
    }

    public function tags(): array
    {
        return $this->pdo->query('SELECT name, slug FROM tags ORDER BY name')->fetchAll();
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
