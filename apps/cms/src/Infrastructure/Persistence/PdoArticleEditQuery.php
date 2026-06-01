<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Edit\Query\IArticleQuery;
use App\Cms\Application\Article\Edit\Value\Article;
use App\Framework\Database\PdoHelper;
use App\Framework\Database\PdoRow;
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

        $pdoRow = PdoRow::from(values: $row);

        return new Article(
            id: $pdoRow->int(key: 'id'),
            title: $pdoRow->string(key: 'title'),
            slug: $pdoRow->string(key: 'slug'),
            bodyMarkdown: $pdoRow->string(key: 'body_markdown'),
            bodyFormat: $pdoRow->string(key: 'body_format', default: 'markdown'),
            excerpt: $pdoRow->string(key: 'excerpt'),
            status: $pdoRow->string(key: 'status'),
            categoryId: $pdoRow->nullableInt(key: 'category_id'),
            eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
            publishedAt: $pdoRow->string(key: 'published_at'),
            publishedEndsAt: $pdoRow->string(key: 'published_ends_at'),
            seoTitle: $pdoRow->string(key: 'seo_title'),
            metaDescription: $pdoRow->string(key: 'meta_description'),
        );
    }
}
