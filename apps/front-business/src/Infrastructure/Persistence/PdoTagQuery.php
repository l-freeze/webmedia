<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\FrontBusiness\Application\Tag;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoTagQuery implements Tag\List\Query\ITagQuery, Tag\Show\Query\ITagQuery, Tag\Show\Query\IArticleQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Tag\List\Value\Tag> */
    public function all(): array
    {
        $tags = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT t.*, COUNT(a.id) AS article_count FROM tags t INNER JOIN article_tags at ON at.tag_id = t.id INNER JOIN articles a ON a.id = at.article_id AND ' . $this->publicCondition() . ' INNER JOIN categories c ON c.id = a.category_id AND ' . BusinessCategoryScope::condition(categoryAlias: 'c') . ' GROUP BY t.id ORDER BY t.name')) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $tags[] = new Tag\List\Value\Tag(
                id: $pdoRow->int(key: 'id'),
                name: $pdoRow->string(key: 'name'),
                slug: $pdoRow->string(key: 'slug'),
                articleCount: $pdoRow->int(key: 'article_count'),
            );
        }

        return $tags;
    }

    public function find(string $slug): ?Tag\Show\Value\TagDetail
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT t.name, t.slug FROM tags t INNER JOIN article_tags at ON at.tag_id = t.id INNER JOIN articles a ON a.id = at.article_id AND ' . $this->publicCondition() . ' INNER JOIN categories c ON c.id = a.category_id AND ' . BusinessCategoryScope::condition(categoryAlias: 'c') . ' WHERE t.slug = :slug GROUP BY t.id');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }
        $pdoRow = PdoRow::from(values: $row);

        return new Tag\Show\Value\TagDetail(name: $pdoRow->string(key: 'name'), slug: $pdoRow->string(key: 'slug'));
    }

    /** @return array<int, Tag\Show\Value\Article> */
    public function articles(string $slug): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT a.*, c.name AS category_name FROM articles a INNER JOIN article_tags at ON at.article_id = a.id INNER JOIN tags t ON t.id = at.tag_id INNER JOIN categories c ON c.id = a.category_id WHERE t.slug = :slug AND ' . BusinessCategoryScope::condition(categoryAlias: 'c') . ' AND ' . $this->publicCondition() . ' ORDER BY a.published_at DESC');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $articles = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $articles[] = new Tag\Show\Value\Article(
                id: $pdoRow->int(key: 'id'),
                title: $pdoRow->string(key: 'title'),
                slug: $pdoRow->string(key: 'slug'),
                excerpt: $pdoRow->string(key: 'excerpt'),
                categoryName: $pdoRow->string(key: 'category_name', default: '未分類') ?: '未分類',
                eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
                publishedAt: $pdoRow->date(key: 'published_at'),
                updatedAt: $pdoRow->date(key: 'updated_at'),
            );
        }

        return $articles;
    }

    private function publicCondition(): string
    {
        return "a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW() AND (a.published_ends_at IS NULL OR a.published_ends_at > NOW())";
    }
}
