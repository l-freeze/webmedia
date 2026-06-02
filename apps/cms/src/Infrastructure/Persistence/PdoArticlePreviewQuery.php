<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Preview\Query\IArticleQuery;
use App\Cms\Application\Article\Preview\Value\Article;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
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

        $pdoRow = PdoRow::from(values: $row);

        return new Article(
            id: $pdoRow->int(key: 'id'),
            title: $pdoRow->string(key: 'title'),
            slug: $pdoRow->string(key: 'slug'),
            bodyMarkdown: $pdoRow->string(key: 'body_markdown'),
            bodyFormat: $pdoRow->string(key: 'body_format', default: 'markdown'),
            status: $pdoRow->string(key: 'status'),
            statusLabel: PdoArticleRepository::statusLabel(status: $pdoRow->string(key: 'status'), publishedAt: $pdoRow->string(key: 'published_at'), publishedEndsAt: $pdoRow->string(key: 'published_ends_at')),
            eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
            publishedAt: $pdoRow->string(key: 'published_at'),
        );
    }
}
