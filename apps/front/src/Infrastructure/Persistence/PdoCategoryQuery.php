<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Front\Application\Category\List\Query\ICategoryQuery as ListCategoryQuery;
use App\Front\Application\Category\List\Value\Category as ListCategory;
use App\Front\Application\Category\Show\Query\IArticleQuery as ShowArticleQuery;
use App\Front\Application\Category\Show\Query\ICategoryQuery as ShowCategoryQuery;
use App\Front\Application\Category\Show\Value\Article as ShowArticle;
use App\Front\Application\Category\Show\Value\CategoryDetail;
use App\Front\Application\Home\Show\Query\ICategoryQuery as HomeCategoryQuery;
use App\Front\Application\Home\Show\Value\Category as HomeCategory;
use PDO;

final readonly class PdoCategoryQuery implements HomeCategoryQuery, ListCategoryQuery, ShowCategoryQuery, ShowArticleQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, HomeCategory|ListCategory> */
    public function all(): array
    {
        return array_map($this->category(...), $this->pdo->query('SELECT c.*, COUNT(a.id) AS article_count FROM categories c LEFT JOIN articles a ON a.category_id = c.id AND ' . $this->publicCondition() . ' GROUP BY c.id ORDER BY c.sort_order, c.name')->fetchAll());
    }

    public function find(string $slug): ?CategoryDetail
    {
        $stmt = $this->pdo->prepare('SELECT name, slug, description FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return new CategoryDetail((string) $row['name'], (string) $row['slug'], $row['description'] === null ? null : (string) $row['description']);
    }

    /** @return array<int, ShowArticle> */
    public function articles(string $slug): array
    {
        $stmt = $this->pdo->prepare('SELECT a.*, c.name AS category_name, c.slug AS category_slug FROM articles a INNER JOIN categories c ON c.id = a.category_id WHERE c.slug = :slug AND ' . $this->publicCondition() . ' ORDER BY a.published_at DESC');
        $stmt->execute(['slug' => $slug]);
        return array_map(fn (array $row): ShowArticle => new ShowArticle(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            excerpt: (string) ($row['excerpt'] ?: ''),
            categoryName: (string) $row['category_name'],
            publishedAt: $this->date($row['published_at'] ?? null),
            updatedAt: $this->date($row['updated_at'] ?? null),
        ), $stmt->fetchAll());
    }

    /** @param array<string, mixed> $row */
    private function category(array $row): HomeCategory|ListCategory
    {
        return new HomeCategory(
            id: (int) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            description: $row['description'] === null ? null : (string) $row['description'],
            articleCount: (int) $row['article_count'],
        );
    }

    private function date(mixed $value): string
    {
        return $value ? date('Y-m-d', strtotime((string) $value)) : '';
    }

    private function publicCondition(): string
    {
        return "a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW() AND (a.published_ends_at IS NULL OR a.published_ends_at > NOW())";
    }
}
