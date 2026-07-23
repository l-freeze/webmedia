<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Trash\List\Query\ITrashQuery;
use App\Cms\Application\Trash\List\Value\Trash;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoTrashQuery implements ITrashQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Trash> */
    public function list(): array
    {
        $items = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT title, slug, updated_at FROM articles WHERE status = 'trashed' UNION ALL SELECT title, slug, updated_at FROM pages WHERE status = 'trashed' ORDER BY updated_at DESC")) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $items[] = new Trash(
                title: $pdoRow->string(key: 'title'),
                slug: $pdoRow->string(key: 'slug'),
                updatedAt: $pdoRow->string(key: 'updated_at'),
            );
        }

        return $items;
    }
}
