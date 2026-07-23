<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Page\List\Query\IPageQuery;
use App\Cms\Application\Page\List\Value\Page;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoPageQuery implements IPageQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Page> */
    public function list(): array
    {
        $pages = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, title, slug, status, display_order, updated_at FROM pages ORDER BY display_order, id')) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $pages[] = new Page(
                id: $pdoRow->int(key: 'id'),
                title: $pdoRow->string(key: 'title'),
                slug: $pdoRow->string(key: 'slug'),
                status: $pdoRow->string(key: 'status'),
                displayOrder: $pdoRow->int(key: 'display_order'),
                updatedAt: $pdoRow->string(key: 'updated_at'),
            );
        }

        return $pages;
    }
}
