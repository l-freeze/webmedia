<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Preview\Query\IArticleQuery;
use App\Cms\Application\Article\Preview\Value\Article;
use PDO;

final readonly class PdoArticlePreviewQuery implements IArticleQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?Article
    {
        $stmt = $this->pdo->prepare('SELECT id, title, slug, body_markdown, status, published_at, published_ends_at FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return new Article(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            bodyMarkdown: (string) $row['body_markdown'],
            status: (string) $row['status'],
            statusLabel: PdoArticleRepository::statusLabel((string) $row['status'], (string) ($row['published_at'] ?? ''), (string) ($row['published_ends_at'] ?? '')),
            publishedAt: (string) ($row['published_at'] ?? ''),
        );
    }
}
