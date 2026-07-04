<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Domain\Entity\Article;
use App\Cms\Domain\Repository\IArticleRepository;
use App\Cms\Domain\Value\ArticleId;
use App\Cms\Domain\Value\ArticleVersionNo;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use App\Shared\Markdown\Infrastructure\Markdown;
use PDO;
use Throwable;

final readonly class PdoArticleRepository implements IArticleRepository
{
    public function __construct(private PDO $pdo, private Markdown $markdown)
    {
    }

    public function findById(ArticleId $articleId): ?Article
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT * FROM articles WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['id' => $articleId->value]);

        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        return $this->articleFromRow(id: $articleId->value, row: $row);
    }

    public function findVersion(ArticleId $articleId, ArticleVersionNo $versionNo): ?Article
    {
        $stmt = PdoHelper::prepare(
            pdo: $this->pdo,
            sql: 'SELECT * FROM article_versions WHERE article_id = :id AND version_no = :version_no',
        );
        PdoHelper::execute(statement: $stmt, params: [
            'id' => $articleId->value,
            'version_no' => $versionNo->value,
        ]);

        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }

        return $this->articleFromRow(id: $articleId->value, row: $row);
    }

    public function save(Article $article): ArticleId
    {
        $this->pdo->beginTransaction();

        try {
            $articleId = $this->saveWithinTransaction(article: $article);
            $this->pdo->commit();
            return $articleId;
        } catch (Throwable $exception) {
            $this->pdo->rollBack();
            throw $exception;
        }
    }

    public function slugExists(string $slug, ?int $exceptId): bool
    {
        $sql = 'SELECT COUNT(*) FROM articles WHERE slug = :slug';
        $params = ['slug' => $slug];
        if ($exceptId !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $exceptId;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $stmt, params: $params);

        return PdoRow::from(values: ['count' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'count') > 0;
    }

    private function saveWithinTransaction(Article $article): ArticleId
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'title' => $article->title->value,
            'slug' => $article->slug->value,
            'body' => $article->body->value,
            'body_format' => $article->bodyFormat->value,
            'rendered_body_html' => $this->markdown->bodyToSafeHtml(body: $article->body->value, format: $article->bodyFormat->value),
            'search_body_text' => $this->markdown->bodyToPlainText(body: $article->body->value, format: $article->bodyFormat->value),
            'excerpt' => $article->excerpt->value,
            'status' => $article->status->value,
            'category_id' => $article->categoryId?->value,
            'eyecatch_path' => $article->eyecatchPath?->value,
            'published_at' => $article->publishedAt?->value,
            'published_ends_at' => $article->publishedEndsAt?->value,
            'seo_title' => $article->seoTitle->value,
            'meta_description' => $article->metaDescription->value,
            'updated_at' => $now,
        ];

        if ($article->id === null) {
            $payload['created_at'] = $now;
            $payload['author_name'] = '編集部';
            $payload['author_id'] = $article->authorId;
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO articles (title, slug, body, body_format, rendered_body_html, search_body_text, excerpt, status, category_id, eyecatch_path, published_at, published_ends_at, seo_title, meta_description, author_name, author_id, created_at, updated_at) VALUES (:title, :slug, :body, :body_format, :rendered_body_html, :search_body_text, :excerpt, :status, :category_id, :eyecatch_path, :published_at, :published_ends_at, :seo_title, :meta_description, :author_name, :author_id, :created_at, :updated_at) RETURNING id');
            PdoHelper::execute(statement: $stmt, params: $payload);
            $id = PdoRow::from(values: ['id' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'id');
        } else {
            $id = $article->id->value;
            $payload['id'] = $article->id->value;
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE articles SET title = :title, slug = :slug, body = :body, body_format = :body_format, rendered_body_html = :rendered_body_html, search_body_text = :search_body_text, excerpt = :excerpt, status = :status, category_id = :category_id, eyecatch_path = :eyecatch_path, published_at = :published_at, published_ends_at = :published_ends_at, seo_title = :seo_title, meta_description = :meta_description, updated_at = :updated_at WHERE id = :id');
            PdoHelper::execute(statement: $stmt, params: $payload);
        }

        $versionNo = $article->id === null ? 1 : $this->nextVersionNo(articleId: $id);
        $this->snapshot(articleId: $id, versionNo: $versionNo, payload: $payload);
        $this->updateCurrentVersionNo(articleId: $id, versionNo: $versionNo);

        return new ArticleId(value: $id);
    }

    /** @param array<string, mixed> $payload */
    private function snapshot(int $articleId, int $versionNo, array $payload): void
    {
        $insert = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO article_versions (article_id, version_no, title, slug, body, body_format, excerpt, status, category_id, eyecatch_path, published_at, published_ends_at, seo_title, meta_description, note, created_by, created_at) VALUES (:article_id, :version_no, :title, :slug, :body, :body_format, :excerpt, :status, :category_id, :eyecatch_path, :published_at, :published_ends_at, :seo_title, :meta_description, :note, :created_by, :created_at)');
        PdoHelper::execute(statement: $insert, params: [
            'article_id' => $articleId,
            'version_no' => $versionNo,
            'title' => $payload['title'],
            'slug' => $payload['slug'],
            'body' => $payload['body'],
            'body_format' => $payload['body_format'],
            'excerpt' => $payload['excerpt'],
            'status' => $payload['status'],
            'category_id' => $payload['category_id'],
            'eyecatch_path' => $payload['eyecatch_path'],
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
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT current_version_no FROM articles WHERE id = :id FOR UPDATE');
        PdoHelper::execute(statement: $stmt, params: ['id' => $articleId]);
        $currentVersionNo = PdoRow::from(values: ['version_no' => PdoHelper::fetchColumn(statement: $stmt)])->int(key: 'version_no');

        $maxStmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT COALESCE(MAX(version_no), 0) FROM article_versions WHERE article_id = :id');
        PdoHelper::execute(statement: $maxStmt, params: ['id' => $articleId]);
        $maxVersionNo = PdoRow::from(values: ['version_no' => PdoHelper::fetchColumn(statement: $maxStmt)])->int(key: 'version_no');

        return max($currentVersionNo, $maxVersionNo) + 1;
    }

    private function updateCurrentVersionNo(int $articleId, int $versionNo): void
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'UPDATE articles SET current_version_no = :version_no WHERE id = :id');
        PdoHelper::execute(statement: $stmt, params: ['version_no' => $versionNo, 'id' => $articleId]);
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

    /** @param array<string, mixed> $row */
    private function articleFromRow(int $id, array $row): Article
    {
        return Article::fromInput(id: $id, data: [
            'title' => $row['title'] ?? '',
            'slug' => $row['slug'] ?? '',
            'body' => $row['body'] ?? '',
            'body_format' => $row['body_format'] ?? 'markdown',
            'excerpt' => $row['excerpt'] ?? '',
            'status' => $row['status'] ?? 'draft',
            'category_id' => $row['category_id'] ?? '',
            'eyecatch_path' => $row['eyecatch_path'] ?? '',
            'published_at' => $row['published_at'] ?? '',
            'published_ends_at' => $row['published_ends_at'] ?? '',
            'seo_title' => $row['seo_title'] ?? '',
            'meta_description' => $row['meta_description'] ?? '',
            'author_id' => $row['author_id'] ?? '',
        ]);
    }
}
