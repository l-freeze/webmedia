<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Page\List\Query\IPageQuery;
use App\Cms\Application\Page\List\Value\Page;
use App\Framework\Database\PdoHelper;
use App\Framework\Database\PdoRow;
use PDO;

final readonly class PdoPageQuery implements IPageQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Page> */
    public function list(): array
    {
        return array_map(static function (array $row): Page {
            $pdoRow = PdoRow::from(values: $row);

            return new Page(
                id: $pdoRow->int(key: 'id'),
                title: $pdoRow->string(key: 'title'),
                slug: $pdoRow->string(key: 'slug'),
                status: $pdoRow->string(key: 'status'),
                displayOrder: $pdoRow->int(key: 'display_order'),
                updatedAt: $pdoRow->string(key: 'updated_at'),
            );
        }, PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, title, slug, status, display_order, updated_at FROM pages ORDER BY display_order, id')));
    }
}
