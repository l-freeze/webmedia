<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Edit\Query\ICategoryQuery;
use App\Cms\Application\Article\Edit\Value\Category;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoArticleEditCategoryQuery implements ICategoryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Category> */
    public function categories(): array
    {
        $categories = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, name FROM categories ORDER BY sort_order, name')) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $categories[] = new Category(
                id: $pdoRow->int(key: 'id'),
                name: $pdoRow->string(key: 'name'),
            );
        }

        return $categories;
    }
}
