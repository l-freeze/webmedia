<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Page\List\Query\IPageQuery;
use App\Cms\Application\Page\List\Value\Page;
use PDO;

final readonly class PdoPageQuery implements IPageQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Page> */
    public function list(): array
    {
        return array_map(fn (array $row): Page => new Page(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            status: (string) $row['status'],
            displayOrder: (int) $row['display_order'],
            updatedAt: (string) $row['updated_at'],
        ), $this->pdo->query('SELECT id, title, slug, status, display_order, updated_at FROM pages ORDER BY display_order, id')->fetchAll());
    }
}
