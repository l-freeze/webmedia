<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Framework\Database\PdoHelper;
use App\Front\Application\Article\Search\Query\IArticleQuery as SearchArticleQuery;
use App\Front\Application\Article\Search\Value\Article as SearchArticle;
use App\Front\Application\Article\Show\Query\IArticleQuery as ShowArticleQuery;
use App\Front\Application\Article\Show\Value\Article as ShowArticle;
use App\Front\Application\Home\Show\Query\IArticleQuery as HomeArticleQuery;
use App\Front\Application\Home\Show\Value\Article as HomeArticle;
use App\Front\Application\Home\Show\Value\ArticleCount;
use App\Framework\Content\Markdown;
use PDO;

final readonly class PdoArticleQuery implements HomeArticleQuery, ShowArticleQuery, SearchArticleQuery
{
    public function __construct(private PDO $pdo, private Markdown $markdown)
    {
    }

    /** @return array<int, HomeArticle> */
    public function latest(int $limit, int $offset): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' ORDER BY a.published_at DESC, a.id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        PdoHelper::execute(statement: $stmt);
        return array_map($this->homeArticle(...), PdoHelper::fetchAll(statement: $stmt));
    }

    public function countPublished(): ArticleCount
    {
        return new ArticleCount(value: (int) PdoHelper::fetchColumn(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT COUNT(*) FROM articles a WHERE ' . $this->publicCondition())));
    }

    public function findPublishedBySlug(string $slug): ?ShowArticle
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' AND a.slug = :slug LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }
        return $this->showArticle(row: $row, bodyHtml: $this->markdown->bodyToSafeHtml(body: (string) $row['body_markdown'], format: (string) ($row['body_format'] ?? 'markdown')), tags: $this->tags(articleId: (int) $row['id']));
    }

    /** @return array<int, SearchArticle> */
    public function search(string $keyword): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' AND (a.title ILIKE :title_q OR a.body_markdown ILIKE :body_q) ORDER BY a.published_at DESC');
        $likeKeyword = '%' . $keyword . '%';
        PdoHelper::execute(statement: $stmt, params: ['title_q' => $likeKeyword, 'body_q' => $likeKeyword]);
        return array_map($this->searchArticle(...), PdoHelper::fetchAll(statement: $stmt));
    }

    /** @return array<int, ShowArticle> */
    public function related(int $articleId, ?int $categoryId): array
    {
        if ($categoryId === null) {
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' AND a.id <> :id ORDER BY a.published_at DESC LIMIT 3');
            PdoHelper::execute(statement: $stmt, params: ['id' => $articleId]);
            return array_map(fn (array $row): ShowArticle => $this->showArticle(row: $row), PdoHelper::fetchAll(statement: $stmt));
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' AND a.id <> :id AND a.category_id = :category_id ORDER BY a.published_at DESC LIMIT 3');
        PdoHelper::execute(statement: $stmt, params: ['id' => $articleId, 'category_id' => $categoryId]);
        return array_map(fn (array $row): ShowArticle => $this->showArticle(row: $row), PdoHelper::fetchAll(statement: $stmt));
    }

    private function baseSql(): string
    {
        return 'SELECT a.*, c.name AS category_name, c.slug AS category_slug FROM articles a LEFT JOIN categories c ON c.id = a.category_id';
    }

    private function publicCondition(): string
    {
        return "a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW() AND (a.published_ends_at IS NULL OR a.published_ends_at > NOW())";
    }

    /** @param array<string, mixed> $row */
    private function homeArticle(array $row): HomeArticle
    {
        return new HomeArticle(...$this->articleArgs(row: $row));
    }

    /** @param array<string, mixed> $row */
    private function searchArticle(array $row): SearchArticle
    {
        return new SearchArticle(...$this->articleArgs(row: $row));
    }

    /** @param array<string, mixed> $row @param array<int, array{name:string,slug:string}> $tags */
    private function showArticle(array $row, ?string $bodyHtml = null, array $tags = []): ShowArticle
    {
        return new ShowArticle(...$this->articleArgs(row: $row), bodyHtml: $bodyHtml, tags: $tags);
    }

    /** @param array<string, mixed> $row @return array<string, mixed> */
    private function articleArgs(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'title' => (string) $row['title'],
            'slug' => (string) $row['slug'],
            'excerpt' => (string) ($row['excerpt'] ?: mb_substr(strip_tags((string) $row['body_markdown']), 0, 120)),
            'categoryId' => $row['category_id'] !== null ? (int) $row['category_id'] : null,
            'categoryName' => (string) ($row['category_name'] ?: '未分類'),
            'categorySlug' => (string) ($row['category_slug'] ?: ''),
            'eyecatchPath' => (string) ($row['eyecatch_path'] ?? ''),
            'publishedAt' => $this->date(value: $row['published_at'] ?? null),
            'updatedAt' => $this->date(value: $row['updated_at'] ?? null),
            'authorName' => (string) ($row['author_name'] ?? '編集部'),
        ];
    }

    /** @return array<int, array{name:string,slug:string}> */
    private function tags(int $articleId): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT t.name, t.slug FROM tags t INNER JOIN article_tags at ON at.tag_id = t.id WHERE at.article_id = :id ORDER BY t.name');
        PdoHelper::execute(statement: $stmt, params: ['id' => $articleId]);
        /** @var array<int, array{name: string, slug: string}> $rows */
        $rows = PdoHelper::fetchAll(statement: $stmt);
        return $rows;
    }

    private function date(mixed $value): string
    {
        return $value ? date('Y-m-d', strtotime((string) $value)) : '';
    }
}
