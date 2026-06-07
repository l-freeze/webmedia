<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Taxonomy\List\Query\ICategoryQuery;
use App\Cms\Application\Taxonomy\List\Query\ITagQuery;
use App\Cms\Application\Taxonomy\List\Value\Category;
use App\Cms\Application\Taxonomy\List\Value\Tag;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoTaxonomyQuery implements ICategoryQuery, ITagQuery
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
