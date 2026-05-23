<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Article\List\Query\IArticleQuery as ListArticleQuery;
use App\Cms\Application\Article\List\Value\Article;
use PDO;

final readonly class PdoArticleQuery implements ListArticleQuery
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array<int, Article> */
    public function list(string $keyword = ''): array
    {
        $sql = 'SELECT a.*, c.name AS category_name FROM articles a LEFT JOIN categories c ON c.id = a.category_id';
        $params = [];
        if ($keyword !== '') {
            $sql .= ' WHERE a.title LIKE :q';
            $params['q'] = '%' . $keyword . '%';
        }
        $sql .= ' ORDER BY a.updated_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return array_map($this->format(...), $stmt->fetchAll());
    }

    /** @param array<string, mixed> $row */
    private function format(array $row): Article
    {
        return new Article(
            id: (int) $row['id'],
            title: (string) $row['title'],
            status: (string) $row['status'],
            statusLabel: PdoArticleRepository::statusLabel((string) $row['status']),
            categoryName: $row['category_name'] === null ? null : (string) $row['category_name'],
            updatedAt: date('Y-m-d H:i', strtotime((string) $row['updated_at'])),
        );
    }
}
