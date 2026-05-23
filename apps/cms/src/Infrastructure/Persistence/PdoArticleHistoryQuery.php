<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\History\Query\IArticleHistoryQuery;
use App\Cms\Application\Article\History\Value\ArticleVersion;
use PDO;

final readonly class PdoArticleHistoryQuery implements IArticleHistoryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, ArticleVersion> */
    public function versions(int $articleId): array
    {
        $stmt = $this->pdo->prepare('SELECT version_no, title, status, created_at FROM article_versions WHERE article_id = :id ORDER BY version_no DESC');
        $stmt->execute(['id' => $articleId]);
        return array_map(fn (array $row): ArticleVersion => new ArticleVersion(
            versionNo: (int) $row['version_no'],
            title: (string) $row['title'],
            status: (string) $row['status'],
            createdAt: (string) $row['created_at'],
        ), $stmt->fetchAll());
    }
}
