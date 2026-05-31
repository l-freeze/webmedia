<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Framework\Database\PdoHelper;
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
        ), PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT t.*, COUNT(a.id) AS article_count FROM tags t LEFT JOIN article_tags at ON at.tag_id = t.id LEFT JOIN articles a ON a.id = at.article_id AND ' . $this->publicCondition() . ' GROUP BY t.id ORDER BY t.name')));
    }

    public function find(string $slug): ?TagDetail
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT name, slug FROM tags WHERE slug = :slug');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }
        return new TagDetail(name: (string) $row['name'], slug: (string) $row['slug']);
    }

    /** @return array<int, ShowArticle> */
    public function articles(string $slug): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT a.*, c.name AS category_name FROM articles a INNER JOIN article_tags at ON at.article_id = a.id INNER JOIN tags t ON t.id = at.tag_id LEFT JOIN categories c ON c.id = a.category_id WHERE t.slug = :slug AND ' . $this->publicCondition() . ' ORDER BY a.published_at DESC');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        return array_map(fn (array $row): ShowArticle => new ShowArticle(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            excerpt: (string) ($row['excerpt'] ?: ''),
            categoryName: (string) ($row['category_name'] ?: '未分類'),
            eyecatchPath: (string) ($row['eyecatch_path'] ?? ''),
            publishedAt: $this->date(value: $row['published_at'] ?? null),
            updatedAt: $this->date(value: $row['updated_at'] ?? null),
        ), PdoHelper::fetchAll(statement: $stmt));
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
