<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Tag\List\Query\ITagQuery;
use App\Cms\Application\Tag\List\Value\Tag;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoTagQuery implements ITagQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Tag> */
    public function tags(): array
    {
        $tags = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT id, name, slug FROM tags ORDER BY name')) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $tags[] = new Tag(
                id: $pdoRow->int(key: 'id'),
                name: $pdoRow->string(key: 'name'),
                slug: $pdoRow->string(key: 'slug'),
            );
        }

        return $tags;
    }
}
