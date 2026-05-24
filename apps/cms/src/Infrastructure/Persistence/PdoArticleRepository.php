<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleVersionNo;
use PDO;

final readonly class PdoArticleRepository implements IArticleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(Article $article): ArticleId
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'title' => $article->title->value,
            'slug' => $article->slug->value,
            'body_markdown' => $article->bodyMarkdown->value,
            'excerpt' => $article->excerpt->value,
            'status' => $article->status->value,
            'category_id' => $article->categoryId?->value,
            'published_at' => $article->publishedAt?->value,
            'published_ends_at' => $article->publishedEndsAt?->value,
            'seo_title' => $article->seoTitle->value,
            'meta_description' => $article->metaDescription->value,
            'updated_at' => $now,
        ];

        if ($article->id === null) {
            $payload['created_at'] = $now;
            $stmt = $this->pdo->prepare('INSERT INTO articles (title, slug, body_markdown, excerpt, status, category_id, published_at, published_ends_at, seo_title, meta_description, author_name, created_at, updated_at) VALUES (:title, :slug, :body_markdown, :excerpt, :status, :category_id, :published_at, :published_ends_at, :seo_title, :meta_description, "編集部", :created_at, :updated_at)');
            $stmt->execute($payload);
            $id = (int) $this->pdo->lastInsertId();
        } else {
            $id = $article->id->value;
            $payload['id'] = $article->id->value;
            $stmt = $this->pdo->prepare('UPDATE articles SET title = :title, slug = :slug, body_markdown = :body_markdown, excerpt = :excerpt, status = :status, category_id = :category_id, published_at = :published_at, published_ends_at = :published_ends_at, seo_title = :seo_title, meta_description = :meta_description, updated_at = :updated_at WHERE id = :id');
            $stmt->execute($payload);
        }

        $this->snapshot($id, $payload);
        return new ArticleId($id);
    }

    public function restore(ArticleId $articleId, ArticleVersionNo $versionNo): void
    {
        $stmt = $this->pdo->prepare('SELECT * FROM article_versions WHERE article_id = :id AND version_no = :version_no');
        $stmt->execute(['id' => $articleId->value, 'version_no' => $versionNo->value]);
        $version = $stmt->fetch();
        if (!$version) {
            return;
        }
        $this->save(Article::fromInput($articleId->value, [
            'title' => $version['title'],
            'slug' => $this->find($articleId->value)['slug'] ?? ('article-' . $articleId->value),
            'body_markdown' => $version['body_markdown'],
            'excerpt' => '',
            'status' => $version['status'],
            'category_id' => '',
            'published_at' => '',
            'published_ends_at' => '',
        ]));
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

    public static function statusLabel(string $status, ?string $publishedAt = null, ?string $publishedEndsAt = null, ?\DateTimeImmutable $now = null): string
    {
        if ($status === 'published') {
            $now ??= new \DateTimeImmutable();
            if ($publishedAt === null || $publishedAt === '') {
                return '表示（公開日時未設定）';
            }
            $publishedAtDate = new \DateTimeImmutable($publishedAt);
            if ($publishedAtDate > $now) {
                return '公開予約';
            }
            if ($publishedEndsAt !== null && $publishedEndsAt !== '' && new \DateTimeImmutable($publishedEndsAt) <= $now) {
                return '公開終了';
            }
            return '公開中';
        }

        return match ($status) {
            'private' => '非表示',
            'trashed' => 'ゴミ箱',
            default => '下書き',
        };
    }

    /** @return array<string, mixed>|null */
    private function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM articles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
