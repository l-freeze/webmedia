<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Category\List\Query\ICategoryQuery;
use App\Cms\Application\Category\List\Value\Category;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoCategoryQuery implements ICategoryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Category> */
    public function categories(): array
    {
        $categories = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, name, slug, description FROM categories ORDER BY sort_order, name')) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $categories[] = new Category(
                id: $pdoRow->int(key: 'id'),
                name: $pdoRow->string(key: 'name'),
                slug: $pdoRow->string(key: 'slug'),
                description: $pdoRow->string(key: 'description'),
            );
        }

        return $categories;
    }
}
