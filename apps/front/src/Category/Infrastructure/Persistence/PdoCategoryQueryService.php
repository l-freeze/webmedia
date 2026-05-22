<?php

declare(strict_types=1);

namespace App\Front\Category\Infrastructure\Persistence;

use App\Front\Category\Domain\CategoryQueryService;
use PDO;

final readonly class PdoCategoryQueryService implements CategoryQueryService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT c.*, COUNT(a.id) AS article_count FROM categories c LEFT JOIN articles a ON a.category_id = c.id AND a.status = "published" GROUP BY c.id ORDER BY c.sort_order, c.name')->fetchAll();
    }

    public function find(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT name, slug, description FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function articles(string $slug): array
    {
        $stmt = $this->pdo->prepare('SELECT a.*, c.name AS category_name, c.slug AS category_slug FROM articles a INNER JOIN categories c ON c.id = a.category_id WHERE c.slug = :slug AND a.status = "published" ORDER BY a.published_at DESC');
        $stmt->execute(['slug' => $slug]);
        return array_map(fn (array $row): array => [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'excerpt' => $row['excerpt'] ?: '',
            'category_name' => $row['category_name'],
            'published_at' => date('Y-m-d', strtotime((string) $row['published_at'])),
            'updated_at' => date('Y-m-d', strtotime((string) $row['updated_at'])),
        ], $stmt->fetchAll());
    }
}
