<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\History\Query\IArticleHistoryQuery;
use App\Cms\Application\Article\History\Value\ArticleVersion;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoArticleHistoryQuery implements IArticleHistoryQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, ArticleVersion> */
    public function versions(int $articleId): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT version_no, title, status, created_at FROM article_versions WHERE article_id = :id ORDER BY version_no DESC');
        PdoHelper::execute(statement: $stmt, params: ['id' => $articleId]);
        return array_map(static function (array $row): ArticleVersion {
            $pdoRow = PdoRow::from(values: $row);

            return new ArticleVersion(
                versionNo: $pdoRow->int(key: 'version_no'),
                title: $pdoRow->string(key: 'title'),
                status: $pdoRow->string(key: 'status'),
                createdAt: $pdoRow->string(key: 'created_at'),
            );
        }, PdoHelper::fetchAll(statement: $stmt));
    }
}
