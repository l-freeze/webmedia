<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Trash\List\Query\ITrashQuery;
use App\Cms\Application\Trash\List\Value\Trash;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoTrashQuery implements ITrashQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Trash> */
    public function list(): array
    {
        return array_map(fn (array $row): Trash => new Trash(
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            updatedAt: (string) $row['updated_at'],
        ), PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT title, slug, updated_at FROM articles WHERE status = 'trashed' UNION ALL SELECT title, slug, updated_at FROM pages WHERE status = 'trashed' ORDER BY updated_at DESC")));
    }
}
