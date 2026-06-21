<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\Policy\Query\IArticlePolicyQuery;
use App\Cms\Application\Article\Policy\Value\ArticlePolicyArticle;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoArticlePolicyQuery implements IArticlePolicyQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findById(int $id): ?ArticlePolicyArticle
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT id, status, author_id FROM articles WHERE id = :id LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['id' => $id]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        $pdoRow = PdoRow::from(values: $row);
        $authorId = $pdoRow->nullableInt(key: 'author_id');

        return new ArticlePolicyArticle(
            id: $pdoRow->int(key: 'id'),
            status: $pdoRow->string(key: 'status'),
            authorId: $authorId > 0 ? $authorId : null,
        );
    }
}
