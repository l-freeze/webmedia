<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\ArticleVersion\Preview\Query\IArticleVersionQuery;
use App\Cms\Application\ArticleVersion\Preview\Value\ArticleVersion;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoArticleVersionPreviewQuery implements IArticleVersionQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $articleId, int $versionNo): ?ArticleVersion
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT article_id, version_no, title, slug, body, body_format, excerpt, status, category_id, eyecatch_path, published_at, published_ends_at, seo_title, meta_description, created_at FROM article_versions WHERE article_id = :article_id AND version_no = :version_no LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['article_id' => $articleId, 'version_no' => $versionNo]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        $pdoRow = PdoRow::from(values: $row);

        return new ArticleVersion(
            articleId: $pdoRow->int(key: 'article_id'),
            versionNo: $pdoRow->int(key: 'version_no'),
            title: $pdoRow->string(key: 'title'),
            slug: $pdoRow->string(key: 'slug'),
            body: $pdoRow->string(key: 'body'),
            bodyFormat: $pdoRow->string(key: 'body_format', default: 'markdown'),
            excerpt: $pdoRow->string(key: 'excerpt'),
            status: $pdoRow->string(key: 'status'),
            statusLabel: PdoArticleRepository::statusLabel(status: $pdoRow->string(key: 'status'), publishedAt: $pdoRow->string(key: 'published_at'), publishedEndsAt: $pdoRow->string(key: 'published_ends_at')),
            categoryId: $pdoRow->nullableInt(key: 'category_id'),
            eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
            publishedAt: $pdoRow->string(key: 'published_at'),
            publishedEndsAt: $pdoRow->string(key: 'published_ends_at'),
            seoTitle: $pdoRow->string(key: 'seo_title'),
            metaDescription: $pdoRow->string(key: 'meta_description'),
            createdAt: $pdoRow->string(key: 'created_at'),
        );
    }
}
