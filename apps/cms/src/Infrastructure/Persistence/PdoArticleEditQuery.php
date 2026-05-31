<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Edit\Query\IArticleQuery;
use App\Cms\Application\Article\Edit\Value\Article;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoArticleEditQuery implements IArticleQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $id): ?Article
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT * FROM articles WHERE id = :id');
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
            excerpt: (string) ($row['excerpt'] ?? ''),
            status: (string) $row['status'],
            categoryId: $row['category_id'] === null ? null : (int) $row['category_id'],
            eyecatchPath: (string) ($row['eyecatch_path'] ?? ''),
            publishedAt: (string) ($row['published_at'] ?? ''),
            publishedEndsAt: (string) ($row['published_ends_at'] ?? ''),
            seoTitle: (string) ($row['seo_title'] ?? ''),
            metaDescription: (string) ($row['meta_description'] ?? ''),
        );
    }
}
