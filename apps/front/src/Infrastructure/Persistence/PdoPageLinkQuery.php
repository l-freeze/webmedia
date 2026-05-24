<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

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
        $stmt = $this->pdo->query("SELECT title, slug FROM pages WHERE status = 'published' ORDER BY id");

        return array_map(fn (array $row): PageLink => new PageLink(
            title: (string) $row['title'],
            slug: (string) $row['slug'],
        ), $stmt->fetchAll());
    }
}
