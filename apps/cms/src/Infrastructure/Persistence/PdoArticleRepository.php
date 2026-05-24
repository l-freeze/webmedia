<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleVersionNo;
use PDO;
use Throwable;

final readonly class PdoArticleRepository implements IArticleRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function save(Article $article): ArticleId
    {
        $this->pdo->beginTransaction();

        try {
            $articleId = $this->saveWithinTransaction($article);
            $this->pdo->commit();
            return $articleId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function saveWithinTransaction(Article $article): ArticleId
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
            $payload['author_name'] = '編集部';
            $stmt = $this->pdo->prepare('INSERT INTO articles (title, slug, body_markdown, excerpt, status, category_id, published_at, published_ends_at, seo_title, meta_description, author_name, created_at, updated_at) VALUES (:title, :slug, :body_markdown, :excerpt, :status, :category_id, :published_at, :published_ends_at, :seo_title, :meta_description, :author_name, :created_at, :updated_at) RETURNING id');
            $stmt->execute($payload);
            $id = (int) $stmt->fetchColumn();
        } else {
            $id = $article->id->value;
            $payload['id'] = $article->id->value;
            $stmt = $this->pdo->prepare('UPDATE articles SET title = :title, slug = :slug, body_markdown = :body_markdown, excerpt = :excerpt, status = :status, category_id = :category_id, published_at = :published_at, published_ends_at = :published_ends_at, seo_title = :seo_title, meta_description = :meta_description, updated_at = :updated_at WHERE id = :id');
            $stmt->execute($payload);
        }

        $versionNo = $article->id === null ? 1 : $this->nextVersionNo($id);
        $this->snapshot($id, $versionNo, $payload);
        $this->updateCurrentVersionNo($id, $versionNo);

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
            'slug' => $version['slug'],
            'body_markdown' => $version['body_markdown'],
            'excerpt' => $version['excerpt'] ?? '',
            'status' => $version['status'],
            'category_id' => $version['category_id'] ?? '',
            'published_at' => $version['published_at'] ?? '',
            'published_ends_at' => $version['published_ends_at'] ?? '',
            'seo_title' => $version['seo_title'] ?? '',
            'meta_description' => $version['meta_description'] ?? '',
        ]));
    }

    /** @param array<string, mixed> $payload */
    private function snapshot(int $articleId, int $versionNo, array $payload): void
    {
        $insert = $this->pdo->prepare('INSERT INTO article_versions (article_id, version_no, title, slug, body_markdown, excerpt, status, category_id, published_at, published_ends_at, seo_title, meta_description, note, created_by, created_at) VALUES (:article_id, :version_no, :title, :slug, :body_markdown, :excerpt, :status, :category_id, :published_at, :published_ends_at, :seo_title, :meta_description, :note, :created_by, :created_at)');
        $insert->execute([
            'article_id' => $articleId,
            'version_no' => $versionNo,
            'title' => $payload['title'],
            'slug' => $payload['slug'],
            'body_markdown' => $payload['body_markdown'],
            'excerpt' => $payload['excerpt'],
            'status' => $payload['status'],
            'category_id' => $payload['category_id'],
            'published_at' => $payload['published_at'],
            'published_ends_at' => $payload['published_ends_at'],
            'seo_title' => $payload['seo_title'],
            'meta_description' => $payload['meta_description'],
            'note' => '保存',
            'created_by' => '編集部',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function nextVersionNo(int $articleId): int
    {
        $stmt = $this->pdo->prepare('SELECT current_version_no FROM articles WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $articleId]);
        $currentVersionNo = (int) $stmt->fetchColumn();

        $maxStmt = $this->pdo->prepare('SELECT COALESCE(MAX(version_no), 0) FROM article_versions WHERE article_id = :id');
        $maxStmt->execute(['id' => $articleId]);
        $maxVersionNo = (int) $maxStmt->fetchColumn();

        return max($currentVersionNo, $maxVersionNo) + 1;
    }

    private function updateCurrentVersionNo(int $articleId, int $versionNo): void
    {
        $stmt = $this->pdo->prepare('UPDATE articles SET current_version_no = :version_no WHERE id = :id');
        $stmt->execute(['version_no' => $versionNo, 'id' => $articleId]);
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

}
