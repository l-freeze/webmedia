<?php

declare(strict_types=1);

namespace App\Cms\Article\Infrastructure\Persistence;

use App\Cms\Article\Domain\ArticleRepository;
use PDO;

final readonly class PdoArticleRepository implements ArticleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

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

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function categories(): array
    {
        return $this->pdo->query('SELECT id, name FROM categories ORDER BY sort_order, name')->fetchAll();
    }

    public function save(?int $id, array $data): int
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'title' => (string) $data['title'],
            'slug' => (string) $data['slug'],
            'body_markdown' => (string) $data['body_markdown'],
            'excerpt' => (string) ($data['excerpt'] ?? ''),
            'status' => (string) ($data['status'] ?? 'draft'),
            'category_id' => ($data['category_id'] ?? '') === '' ? null : (int) $data['category_id'],
            'published_at' => ($data['published_at'] ?? '') === '' ? null : (string) $data['published_at'],
            'scheduled_at' => ($data['scheduled_at'] ?? '') === '' ? null : (string) $data['scheduled_at'],
            'seo_title' => (string) ($data['seo_title'] ?? ''),
            'meta_description' => (string) ($data['meta_description'] ?? ''),
            'updated_at' => $now,
        ];

        if ($id === null) {
            $payload['created_at'] = $now;
            $stmt = $this->pdo->prepare('INSERT INTO articles (title, slug, body_markdown, excerpt, status, category_id, published_at, scheduled_at, seo_title, meta_description, author_name, created_at, updated_at) VALUES (:title, :slug, :body_markdown, :excerpt, :status, :category_id, :published_at, :scheduled_at, :seo_title, :meta_description, "編集部", :created_at, :updated_at)');
            $stmt->execute($payload);
            $id = (int) $this->pdo->lastInsertId();
        } else {
            $payload['id'] = $id;
            $stmt = $this->pdo->prepare('UPDATE articles SET title = :title, slug = :slug, body_markdown = :body_markdown, excerpt = :excerpt, status = :status, category_id = :category_id, published_at = :published_at, scheduled_at = :scheduled_at, seo_title = :seo_title, meta_description = :meta_description, updated_at = :updated_at WHERE id = :id');
            $stmt->execute($payload);
        }

        $this->snapshot($id, $payload);
        return $id;
    }

    public function versions(int $articleId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM article_versions WHERE article_id = :id ORDER BY version_no DESC');
        $stmt->execute(['id' => $articleId]);
        return $stmt->fetchAll();
    }

    public function restore(int $articleId, int $versionNo): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM article_versions WHERE article_id = :id AND version_no = :version_no');
        $stmt->execute(['id' => $articleId, 'version_no' => $versionNo]);
        $version = $stmt->fetch();
        if (!$version) {
            return;
        }
        $this->save($articleId, [
            'title' => $version['title'],
            'slug' => $this->find($articleId)['slug'] ?? ('article-' . $articleId),
            'body_markdown' => $version['body_markdown'],
            'excerpt' => '',
            'status' => $version['status'],
            'category_id' => '',
            'published_at' => '',
            'scheduled_at' => '',
        ]);
    }

    public function stats(): array
    {
        $rows = $this->pdo->query('SELECT status, COUNT(*) AS count FROM articles GROUP BY status')->fetchAll();
        $stats = ['公開中記事数' => 0, '下書き数' => 0, '公開予約数' => 0, '非公開記事数' => 0];
        foreach ($rows as $row) {
            $label = match ($row['status']) {
                'published' => '公開中記事数',
                'scheduled' => '公開予約数',
                'private' => '非公開記事数',
                default => '下書き数',
            };
            $stats[$label] = (int) $row['count'];
        }
        return $stats;
    }

    /** @param array<string, mixed> $payload */
    private function snapshot(int $articleId, array $payload): void
    {
        $stmt = $this->pdo->prepare('SELECT COALESCE(MAX(version_no), 0) + 1 FROM article_versions WHERE article_id = :id');
        $stmt->execute(['id' => $articleId]);
        $versionNo = (int) $stmt->fetchColumn();
        $insert = $this->pdo->prepare('INSERT INTO article_versions (article_id, version_no, title, body_markdown, status, note, created_by, created_at) VALUES (:article_id, :version_no, :title, :body_markdown, :status, :note, :created_by, :created_at)');
        $insert->execute([
            'article_id' => $articleId,
            'version_no' => $versionNo,
            'title' => $payload['title'],
            'body_markdown' => $payload['body_markdown'],
            'status' => $payload['status'],
            'note' => '保存',
            'created_by' => '編集部',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** @param array<string, mixed> $row */
    private function format(array $row): array
    {
        $row['status_label'] = self::statusLabel((string) $row['status']);
        $row['updated_at'] = date('Y-m-d H:i', strtotime((string) $row['updated_at']));
        return $row;
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'published' => '公開中',
            'scheduled' => '公開予約',
            'private' => '非公開',
            'trashed' => 'ゴミ箱',
            default => '下書き',
        };
    }
}
