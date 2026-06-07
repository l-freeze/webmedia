<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\List\Query\IArticleQuery as ListArticleQuery;
use App\Cms\Application\Article\List\Value\Article;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoArticleQuery implements ListArticleQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Article> */
    public function list(string $keyword = ''): array
    {
        $sql = 'SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id = a.category_id WHERE a.status <> :trashed';
        $params = ['trashed' => 'trashed'];
        if ($keyword !== '') {
            $sql .= ' AND a.title LIKE :q';
            $params['q'] = '%' . $keyword . '%';
        }
        $sql .= ' ORDER BY a.updated_at DESC';
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $stmt, params: $params);
        $articles = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $articles[] = $this->format(row: $row);
        }

        return $articles;
    }

    /** @param array<string, mixed> $row */
    private function format(array $row): Article
    {
        $pdoRow = PdoRow::from(values: $row);

        return new Article(
            id: $pdoRow->int(key: 'id'),
            title: $pdoRow->string(key: 'title'),
            status: $pdoRow->string(key: 'status'),
            statusLabel: PdoArticleRepository::statusLabel(status: $pdoRow->string(key: 'status'), publishedAt: $pdoRow->string(key: 'published_at'), publishedEndsAt: $pdoRow->string(key: 'published_ends_at')),
            categoryName: $pdoRow->nullableString(key: 'category_name'),
            updatedAt: $pdoRow->date(key: 'updated_at', format: 'Y-m-d H:i'),
        );
    }
}
