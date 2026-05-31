<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Framework\Content\Markdown;
use App\Framework\Database\PdoHelper;
use App\Front\Application\Page\Show\Query\IPageQuery;
use App\Front\Application\Page\Show\Value\PageDetail;
use PDO;

final readonly class PdoPageQuery implements IPageQuery
{
    public function __construct(private PDO $pdo, private Markdown $markdown)
    {
    }

    public function findPublishedBySlug(string $slug): ?PageDetail
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: "SELECT * FROM pages WHERE slug = :slug AND status = 'published' LIMIT 1");
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }
        return new PageDetail(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            bodyHtml: $this->markdown->toSafeHtml(markdown: (string) $row['body_markdown']),
            updatedAt: date('Y-m-d', strtotime((string) $row['updated_at'])),
        );
    }
}
