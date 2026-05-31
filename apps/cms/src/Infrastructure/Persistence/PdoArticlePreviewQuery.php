<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Preview\Query\IArticleQuery;
use App\Cms\Application\Article\Preview\Value\Article;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoArticlePreviewQuery implements IArticleQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?Article
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, title, slug, body_markdown, body_format, status, eyecatch_path, published_at, published_ends_at FROM articles WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        return new Article(
            id: (int) $row['id'],
            title: (string) $row['title'],
            slug: (string) $row['slug'],
            bodyMarkdown: (string) $row['body_markdown'],
            bodyFormat: (string) ($row['body_format'] ?? 'markdown'),
            status: (string) $row['status'],
            statusLabel: PdoArticleRepository::statusLabel(status: (string) $row['status'], publishedAt: (string) ($row['published_at'] ?? ''), publishedEndsAt: (string) ($row['published_ends_at'] ?? '')),
            eyecatchPath: (string) ($row['eyecatch_path'] ?? ''),
            publishedAt: (string) ($row['published_at'] ?? ''),
        );
    }
}
