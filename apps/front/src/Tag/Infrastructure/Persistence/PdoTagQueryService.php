<?php

declare(strict_types=1);

namespace App\Front\Tag\Infrastructure\Persistence;

use App\Front\Tag\Domain\TagQueryService;
use PDO;

final readonly class PdoTagQueryService implements TagQueryService
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        return $this->pdo->query('SELECT t.*, COUNT(at.article_id) AS article_count FROM tags t LEFT JOIN article_tags at ON at.tag_id = t.id GROUP BY t.id ORDER BY t.name')->fetchAll();
    }

    public function find(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT name, slug FROM tags WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function articles(string $slug): array
    {
        $stmt = $this->pdo->prepare('SELECT a.*, c.name AS category_name FROM articles a INNER JOIN article_tags at ON at.article_id = a.id INNER JOIN tags t ON t.id = at.tag_id LEFT JOIN categories c ON c.id = a.category_id WHERE t.slug = :slug AND a.status = "published" ORDER BY a.published_at DESC');
        $stmt->execute(['slug' => $slug]);
        return array_map(fn (array $row): array => [
            'id' => (int) $row['id'],
            'title' => $row['title'],
            'slug' => $row['slug'],
            'excerpt' => $row['excerpt'] ?: '',
            'category_name' => $row['category_name'] ?: '未分類',
            'published_at' => date('Y-m-d', strtotime((string) $row['published_at'])),
            'updated_at' => date('Y-m-d', strtotime((string) $row['updated_at'])),
        ], $stmt->fetchAll());
    }
}
