<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Framework\Database\PdoHelper;
use App\Framework\Database\PdoRow;
use App\Front\Application\Layout\Query\IPageLinkQuery;
use App\Front\Application\Layout\Value\PageLink;
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

        return array_map(static function (array $row): PageLink {
            $pdoRow = PdoRow::from(values: $row);

            return new PageLink(
                title: $pdoRow->string(key: 'title'),
                slug: $pdoRow->string(key: 'slug'),
            );
        }, PdoHelper::fetchAll(statement: $stmt));
    }
}
