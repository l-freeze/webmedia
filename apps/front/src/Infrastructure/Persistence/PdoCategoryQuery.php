<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Framework\Database\PdoHelper;
use App\Framework\Database\PdoRow;
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
        return array_map($this->category(...), PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT c.*, COUNT(a.id) AS article_count FROM categories c LEFT JOIN articles a ON a.category_id = c.id AND ' . $this->publicCondition() . ' GROUP BY c.id ORDER BY c.sort_order, c.name')));
    }

    public function find(string $slug): ?CategoryDetail
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT name, slug, description FROM categories WHERE slug = :slug');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }
        $pdoRow = PdoRow::from(values: $row);

        return new CategoryDetail(
            name: $pdoRow->string(key: 'name'),
            slug: $pdoRow->string(key: 'slug'),
            description: $pdoRow->nullableString(key: 'description'),
        );
    }

    /** @return array<int, ShowArticle> */
    public function articles(string $slug): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT a.*, c.name AS category_name, c.slug AS category_slug FROM articles a INNER JOIN categories c ON c.id = a.category_id WHERE c.slug = :slug AND ' . $this->publicCondition() . ' ORDER BY a.published_at DESC');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        return array_map(static function (array $row): ShowArticle {
            $pdoRow = PdoRow::from(values: $row);

            return new ShowArticle(
                id: $pdoRow->int(key: 'id'),
                title: $pdoRow->string(key: 'title'),
                slug: $pdoRow->string(key: 'slug'),
                excerpt: $pdoRow->string(key: 'excerpt'),
                categoryName: $pdoRow->string(key: 'category_name'),
                eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
                publishedAt: $pdoRow->date(key: 'published_at'),
                updatedAt: $pdoRow->date(key: 'updated_at'),
            );
        }, PdoHelper::fetchAll(statement: $stmt));
    }

    /** @param array<string, mixed> $row */
    private function category(array $row): HomeCategory
    {
        $pdoRow = PdoRow::from(values: $row);

        return new HomeCategory(
            id: $pdoRow->int(key: 'id'),
            name: $pdoRow->string(key: 'name'),
            slug: $pdoRow->string(key: 'slug'),
            description: $pdoRow->nullableString(key: 'description'),
            articleCount: $pdoRow->int(key: 'article_count'),
        );
    }

    private function publicCondition(): string
    {
        return "a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW() AND (a.published_ends_at IS NULL OR a.published_ends_at > NOW())";
    }
}
