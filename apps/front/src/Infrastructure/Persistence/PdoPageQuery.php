<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Framework\Content\Markdown;
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
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE slug = :slug AND status = "published" LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        return new PageDetail(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            bodyHtml: $this->markdown->toSafeHtml((string) $row['body_markdown']),
            updatedAt: date('Y-m-d', strtotime((string) $row['updated_at'])),
        );
    }
}
