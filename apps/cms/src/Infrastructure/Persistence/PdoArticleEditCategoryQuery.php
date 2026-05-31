<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Edit\Query\ICategoryQuery;
use App\Cms\Application\Article\Edit\Value\Category;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoArticleEditCategoryQuery implements ICategoryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Category> */
    public function categories(): array
    {
        return array_map(fn (array $row): Category => new Category(
            id: (int) $row['id'],
            name: (string) $row['name'],
        ), PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, name FROM categories ORDER BY sort_order, name')));
    }
}
