<?php

declare(strict_types=1);

namespace App\Front\Article\Infrastructure\Persistence;

use App\Framework\Content\Markdown;
use App\Front\Article\Domain\ArticleQueryService;
use PDO;

final readonly class PdoArticleQueryService implements ArticleQueryService
{
    public function __construct(private PDO $pdo, private Markdown $markdown)
    {
    }

    public function latest(int $limit = 10): array
    {
        $stmt = $this->pdo->prepare($this->baseSql() . ' WHERE a.status = "published" ORDER BY a.published_at DESC, a.id DESC LIMIT :limit');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map($this->formatSummary(...), $stmt->fetchAll());
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $stmt = $this->pdo->prepare($this->baseSql() . ' WHERE a.status = "published" AND a.slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        $article = $this->formatSummary($row);
        $article['body_html'] = $this->markdown->toSafeHtml((string) $row['body_markdown']);
        $article['tags'] = $this->tags((int) $row['id']);
        return $article;
    }

    public function search(string $keyword): array
    {
        $stmt = $this->pdo->prepare($this->baseSql() . ' WHERE a.status = "published" AND (a.title LIKE :q OR a.body_markdown LIKE :q) ORDER BY a.published_at DESC');
        $stmt->execute(['q' => '%' . $keyword . '%']);
        return array_map($this->formatSummary(...), $stmt->fetchAll());
    }

    public function related(int $articleId, ?int $categoryId): array
    {
        $stmt = $this->pdo->prepare($this->baseSql() . ' WHERE a.status = "published" AND a.id <> :id AND (:category_id IS NULL OR a.category_id = :category_id) ORDER BY a.published_at DESC LIMIT 3');
        $stmt->execute(['id' => $articleId, 'category_id' => $categoryId]);
        return array_map($this->formatSummary(...), $stmt->fetchAll());
    }

    private function baseSql(): string
    {
        return 'SELECT a.*, c.name AS category_name, c.slug AS category_slug FROM articles a LEFT JOIN categories c ON c.id = a.category_id';
    }

    /** @param array<string, mixed> $row */
    private function formatSummary(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'slug' => (string) $row['slug'],
            'excerpt' => (string) ($row['excerpt'] ?: mb_substr(strip_tags((string) $row['body_markdown']), 0, 120)),
            'category_id' => $row['category_id'] !== null ? (int) $row['category_id'] : null,
            'category_name' => $row['category_name'] ?: '未分類',
            'category_slug' => $row['category_slug'] ?: '',
            'published_at' => $this->date($row['published_at'] ?? null),
            'updated_at' => $this->date($row['updated_at'] ?? null),
            'author_name' => (string) ($row['author_name'] ?? '編集部'),
        ];
    }

    /** @return array<int, array{name:string,slug:string}> */
    private function tags(int $articleId): array
    {
        $stmt = $this->pdo->prepare('SELECT t.name, t.slug FROM tags t INNER JOIN article_tags at ON at.tag_id = t.id WHERE at.article_id = :id ORDER BY t.name');
        $stmt->execute(['id' => $articleId]);
        return $stmt->fetchAll();
    }

    private function date(mixed $value): string
    {
        return $value ? date('Y-m-d', strtotime((string) $value)) : '';
    }
}
