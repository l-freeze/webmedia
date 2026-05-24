<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Page\Edit\Query\IPageQuery;
use App\Cms\Application\Page\Edit\Value\Page;
use PDO;

final readonly class PdoPageEditQuery implements IPageQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?Page
    {
        $stmt = $this->pdo->prepare('SELECT * FROM pages WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return new Page(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            bodyMarkdown: (string) $row['body_markdown'],
            status: (string) $row['status'],
            displayOrder: (int) ($row['display_order'] ?? 0),
            seoTitle: (string) ($row['seo_title'] ?? ''),
            metaDescription: (string) ($row['meta_description'] ?? ''),
        );
    }
}
