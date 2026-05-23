<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Taxonomy\List\Query\ICategoryQuery;
use App\Cms\Application\Taxonomy\List\Query\ITagQuery;
use App\Cms\Application\Taxonomy\List\Value\Category;
use App\Cms\Application\Taxonomy\List\Value\Tag;
use PDO;

final readonly class PdoTaxonomyQuery implements ICategoryQuery, ITagQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Category> */
    public function categories(): array
    {
        return array_map(fn (array $row): Category => new Category(
            name: (string) $row['name'],
            slug: (string) $row['slug'],
            description: (string) ($row['description'] ?? ''),
        ), $this->pdo->query('SELECT name, slug, description FROM categories ORDER BY sort_order, name')->fetchAll());
    }

    /** @return array<int, Tag> */
    public function tags(): array
    {
        return array_map(fn (array $row): Tag => new Tag(
            name: (string) $row['name'],
            slug: (string) $row['slug'],
        ), $this->pdo->query('SELECT name, slug FROM tags ORDER BY name')->fetchAll());
    }
}
