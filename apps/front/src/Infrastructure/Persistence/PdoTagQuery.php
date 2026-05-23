<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Front\Application\Tag\List\Query\ITagQuery as ListTagQuery;
use App\Front\Application\Tag\List\Value\Tag as ListTag;
use App\Front\Application\Tag\Show\Query\IArticleQuery as ShowArticleQuery;
use App\Front\Application\Tag\Show\Query\ITagQuery as ShowTagQuery;
use App\Front\Application\Tag\Show\Value\Article as ShowArticle;
use App\Front\Application\Tag\Show\Value\TagDetail;
use PDO;

final readonly class PdoTagQuery implements ListTagQuery, ShowTagQuery, ShowArticleQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, ListTag> */
    public function all(): array
    {
        return array_map(fn (array $row): ListTag => new ListTag(
            id: (int) $row['id'],
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            articleCount: (int) $row['article_count'],
        ), $this->pdo->query('SELECT t.*, COUNT(at.article_id) AS article_count FROM tags t LEFT JOIN article_tags at ON at.tag_id = t.id GROUP BY t.id ORDER BY t.name')->fetchAll());
    }

    public function find(string $slug): ?TagDetail
    {
        $stmt = $this->pdo->prepare('SELECT name, slug FROM tags WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return new TagDetail((string) $row['name'], (string) $row['slug']);
    }

    /** @return array<int, ShowArticle> */
    public function articles(string $slug): array
    {
        $stmt = $this->pdo->prepare('SELECT a.*, c.name AS category_name FROM articles a INNER JOIN article_tags at ON at.article_id = a.id INNER JOIN tags t ON t.id = at.tag_id LEFT JOIN categories c ON c.id = a.category_id WHERE t.slug = :slug AND a.status = "published" ORDER BY a.published_at DESC');
        $stmt->execute(['slug' => $slug]);
        return array_map(fn (array $row): ShowArticle => new ShowArticle(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            excerpt: (string) ($row['excerpt'] ?: ''),
            categoryName: (string) ($row['category_name'] ?: '未分類'),
            publishedAt: $this->date($row['published_at'] ?? null),
            updatedAt: $this->date($row['updated_at'] ?? null),
        ), $stmt->fetchAll());
    }

    private function date(mixed $value): string
    {
        return $value ? date('Y-m-d', strtotime((string) $value)) : '';
    }
}
