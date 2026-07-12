<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Front\Application\Layout\Load\Query\ICategoryQuery;
use App\Front\Application\Layout\Load\Value\Category;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoLayoutCategoryQuery implements ICategoryQuery
{
    public function __construct(private PDO $pdo, private ArticlePublicationCondition $publication)
    {
    }

    /** @return array<int, Category> */
    public function all(): array
    {
        $categories = [];
        $statement = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT c.*, COUNT(a.id) AS article_count FROM categories c LEFT JOIN articles a ON a.category_id = c.id AND ' . $this->publication->sql() . ' GROUP BY c.id ORDER BY c.sort_order, c.name');
        PdoHelper::execute(statement: $statement, params: $this->publication->parameters());
        foreach (PdoHelper::fetchAll(statement: $statement) as $row) {
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

}
