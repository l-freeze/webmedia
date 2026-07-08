<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\FrontBusiness\Application\Layout\Load\Query\ICategoryQuery;
use App\FrontBusiness\Application\Layout\Load\Value\Category;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoLayoutCategoryQuery implements ICategoryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Category> */
    public function all(): array
    {
        $categories = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT c.*, COUNT(a.id) AS article_count FROM categories c LEFT JOIN articles a ON a.category_id = c.id AND ' . $this->publicCondition() . ' WHERE ' . BusinessCategoryScope::condition(categoryAlias: 'c') . ' GROUP BY c.id ORDER BY c.sort_order, c.name')) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $categories[] = new Category(
                id: $pdoRow->int(key: 'id'),
                name: $pdoRow->string(key: 'name'),
                slug: $pdoRow->string(key: 'slug'),
                description: $pdoRow->nullableString(key: 'description'),
                articleCount: $pdoRow->int(key: 'article_count'),
            );
        }

        return $categories;
    }

    private function publicCondition(): string
    {
        return "a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW() AND (a.published_ends_at IS NULL OR a.published_ends_at > NOW())";
    }
}
