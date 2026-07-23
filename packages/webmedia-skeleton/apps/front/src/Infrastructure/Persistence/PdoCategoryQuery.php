<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Front\Application\Category;
use App\Front\Application\Home;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoCategoryQuery implements Home\Show\Query\ICategoryQuery, Category\List\Query\ICategoryQuery, Category\Show\Query\ICategoryQuery, Category\Show\Query\IArticleQuery
{
    public function __construct(private PDO $pdo, private ArticlePublicationCondition $publication)
    {
    }

    /** @return array<int, Home\Show\Value\Category|Category\List\Value\Category> */
    public function all(): array
    {
        $categories = [];
        $statement = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT c.*, COUNT(a.id) AS article_count FROM categories c LEFT JOIN articles a ON a.category_id = c.id AND ' . $this->publication->sql() . ' GROUP BY c.id ORDER BY c.sort_order, c.name');
        PdoHelper::execute(statement: $statement, params: $this->publication->parameters());
        foreach (PdoHelper::fetchAll(statement: $statement) as $row) {
            $categories[] = $this->category(row: $row);
        }

        return $categories;
    }

    public function find(string $slug): ?Category\Show\Value\CategoryDetail
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT name, slug, description FROM categories WHERE slug = :slug');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }
        $pdoRow = PdoRow::from(values: $row);

        return new Category\Show\Value\CategoryDetail(
            name: $pdoRow->string(key: 'name'),
            slug: $pdoRow->string(key: 'slug'),
            description: $pdoRow->nullableString(key: 'description'),
        );
    }

    /** @return array<int, Category\Show\Value\Article> */
    public function articles(string $slug): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT a.*, c.name AS category_name, c.slug AS category_slug FROM articles a INNER JOIN categories c ON c.id = a.category_id WHERE c.slug = :slug AND ' . $this->publication->sql() . ' ORDER BY a.published_at DESC');
        PdoHelper::execute(statement: $stmt, params: [...$this->publication->parameters(), 'slug' => $slug]);
        $articles = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $articles[] = new Category\Show\Value\Article(
                id: $pdoRow->int(key: 'id'),
                title: $pdoRow->string(key: 'title'),
                slug: $pdoRow->string(key: 'slug'),
                excerpt: $pdoRow->string(key: 'excerpt'),
                categoryName: $pdoRow->string(key: 'category_name'),
                eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
                publishedAt: $pdoRow->string(key: 'published_at'),
                updatedAt: $pdoRow->string(key: 'updated_at'),
            );
        }

        return $articles;
    }

    /** @param array<string, mixed> $row */
    private function category(array $row): Home\Show\Value\Category
    {
        $pdoRow = PdoRow::from(values: $row);

        return new Home\Show\Value\Category(
            id: $pdoRow->int(key: 'id'),
            name: $pdoRow->string(key: 'name'),
            slug: $pdoRow->string(key: 'slug'),
            description: $pdoRow->nullableString(key: 'description'),
            articleCount: $pdoRow->int(key: 'article_count'),
        );
    }

}
