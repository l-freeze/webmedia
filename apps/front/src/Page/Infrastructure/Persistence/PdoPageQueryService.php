<?php

declare(strict_types=1);

namespace App\Front\Page\Infrastructure\Persistence;

use App\Framework\Content\Markdown;
use App\Front\Page\Domain\PageQueryService;
use PDO;

final readonly class PdoPageQueryService implements PageQueryService
{
    public function __construct(private PDO $pdo, private Markdown $markdown)
    {
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE slug = :slug AND status = "published" LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $row['body_html'] = $this->markdown->toSafeHtml((string) $row['body_markdown']);
        $row['updated_at'] = date('Y-m-d', strtotime((string) $row['updated_at']));
        return $row;
    }
}
