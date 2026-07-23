<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use App\Front\Application\Layout\Load\Query\IPageLinkQuery;
use App\Front\Application\Layout\Load\Value\PageLink;
use PDO;

final readonly class PdoPageLinkQuery implements IPageLinkQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, PageLink> */
    public function publishedPageLinks(): array
    {
        $stmt = PdoHelper::query(pdo: $this->pdo, sql: "SELECT title, slug FROM pages WHERE status = 'published' ORDER BY display_order, id");

        $links = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $pdoRow = PdoRow::from(values: $row);

            $links[] = new PageLink(
                title: $pdoRow->string(key: 'title'),
                slug: $pdoRow->string(key: 'slug'),
            );
        }

        return $links;
    }
}
