<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\ArticleVersion\Preview\Query\IArticleVersionQuery;
use App\Cms\Application\ArticleVersion\Preview\Value\ArticleVersion;
use PDO;

final readonly class PdoArticleVersionPreviewQuery implements IArticleVersionQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(int $articleId, int $versionNo): ?ArticleVersion
    {
        $stmt = $this->pdo->prepare('SELECT article_id, version_no, title, body_markdown, status, created_at FROM article_versions WHERE article_id = :article_id AND version_no = :version_no LIMIT 1');
        $stmt->execute(['article_id' => $articleId, 'version_no' => $versionNo]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }

        return new ArticleVersion(
            articleId: (int) $row['article_id'],
            versionNo: (int) $row['version_no'],
            title: (string) $row['title'],
            bodyMarkdown: (string) $row['body_markdown'],
            status: (string) $row['status'],
            statusLabel: PdoArticleRepository::statusLabel((string) $row['status']),
            createdAt: (string) $row['created_at'],
        );
    }
}
