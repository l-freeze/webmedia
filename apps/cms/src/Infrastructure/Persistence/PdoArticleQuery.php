<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\List\Query\IArticleQuery as ListArticleQuery;
use App\Cms\Application\Article\List\Value\Article;
use App\Framework\Database\PdoHelper;
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
        return array_map($this->format(...), PdoHelper::fetchAll(statement: $stmt));
    }

    /** @param array<string, mixed> $row */
    private function format(array $row): Article
    {
        return new Article(
            id: (int) $row['id'],
            title: (string) $row['title'],
            status: (string) $row['status'],
            statusLabel: PdoArticleRepository::statusLabel(status: (string) $row['status'], publishedAt: (string) ($row['published_at'] ?? ''), publishedEndsAt: (string) ($row['published_ends_at'] ?? '')),
            categoryName: $row['category_name'] === null ? null : (string) $row['category_name'],
            updatedAt: date('Y-m-d H:i', strtotime((string) $row['updated_at'])),
        );
    }
}
