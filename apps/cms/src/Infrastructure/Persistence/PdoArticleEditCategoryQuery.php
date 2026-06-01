<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Edit\Query\ICategoryQuery;
use App\Cms\Application\Article\Edit\Value\Category;
use App\Framework\Database\PdoHelper;
use App\Framework\Database\PdoRow;
use PDO;

final readonly class PdoArticleEditCategoryQuery implements ICategoryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Category> */
    public function categories(): array
    {
        return array_map(static function (array $row): Category {
            $pdoRow = PdoRow::from(values: $row);

            return new Category(
                id: $pdoRow->int(key: 'id'),
                name: $pdoRow->string(key: 'name'),
            );
        }, PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, name FROM categories ORDER BY sort_order, name')));
    }
}
