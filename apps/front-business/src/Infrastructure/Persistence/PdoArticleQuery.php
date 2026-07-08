<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\FrontBusiness\Application\Article;
use App\FrontBusiness\Application\Home;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use App\Shared\Markdown\Infrastructure\Markdown;
use PDO;

final readonly class PdoArticleQuery implements Home\Show\Query\IArticleQuery, Article\Show\Query\IArticleQuery, Article\Search\Query\IArticleQuery
{
    private const RELATED_ARTICLES_LIMIT = 3;
    private const GENERATED_EXCERPT_MAX_LENGTH = 120;

    public function __construct(private PDO $pdo, private Markdown $markdown)
    {
    }

    /** @return array<int, Home\Show\Value\Article> */
    public function latest(int $limit, int $offset): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' ORDER BY a.published_at DESC, a.id DESC LIMIT :limit OFFSET :offset');
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        PdoHelper::execute(statement: $stmt);
        $articles = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $articles[] = $this->homeArticle(row: $row);
        }

        return $articles;
    }

    public function countPublished(): Home\Show\Value\ArticleCount
    {
        return new Home\Show\Value\ArticleCount(value: PdoRow::from(['count' => PdoHelper::fetchColumn(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT COUNT(*) FROM articles a INNER JOIN categories c ON c.id = a.category_id WHERE ' . $this->publicCondition()))])->int(key: 'count'));
    }

    public function findPublishedBySlug(string $slug): ?Article\Show\Value\Article
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' AND a.slug = :slug LIMIT 1');
        PdoHelper::execute(statement: $stmt, params: ['slug' => $slug]);
        $row = PdoHelper::fetch(statement: $stmt);
        if ($row === null) {
            return null;
        }
        $pdoRow = PdoRow::from(values: $row);

        return $this->showArticle(
            row: $row,
            bodyHtml: $this->bodyHtml(row: $pdoRow),
            tags: $this->tags(articleId: $pdoRow->int(key: 'id')),
        );
    }

    /** @return array<int, Article\Search\Value\Article> */
    public function search(string $keyword): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' AND (a.title ILIKE :title_q OR a.search_body_text ILIKE :body_q) ORDER BY a.published_at DESC');
        $likeKeyword = '%' . $keyword . '%';
        PdoHelper::execute(statement: $stmt, params: ['title_q' => $likeKeyword, 'body_q' => $likeKeyword]);
        $articles = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $articles[] = $this->searchArticle(row: $row);
        }

        return $articles;
    }

    /** @return array<int, Article\Show\Value\Article> */
    public function related(int $articleId, ?int $categoryId): array
    {
        if ($categoryId === null) {
            $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' AND a.id <> :id ORDER BY a.published_at DESC LIMIT ' . self::RELATED_ARTICLES_LIMIT);
            PdoHelper::execute(statement: $stmt, params: ['id' => $articleId]);
            $articles = [];
            foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
                $articles[] = $this->showArticle(row: $row);
            }

            return $articles;
        }

        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: $this->baseSql() . ' WHERE ' . $this->publicCondition() . ' AND a.id <> :id AND a.category_id = :category_id ORDER BY a.published_at DESC LIMIT ' . self::RELATED_ARTICLES_LIMIT);
        PdoHelper::execute(statement: $stmt, params: ['id' => $articleId, 'category_id' => $categoryId]);
        $articles = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $articles[] = $this->showArticle(row: $row);
        }

        return $articles;
    }

    private function baseSql(): string
    {
        return 'SELECT a.*, c.name AS category_name, c.slug AS category_slug FROM articles a LEFT JOIN categories c ON c.id = a.category_id';
    }

    private function publicCondition(): string
    {
        return "a.status = 'published' AND a.published_at IS NOT NULL AND a.published_at <= NOW() AND (a.published_ends_at IS NULL OR a.published_ends_at > NOW()) AND " . BusinessCategoryScope::condition(categoryAlias: 'c');
    }

    /**
     * @param array<string, mixed> $row
     * @param array<int, array{name:string,slug:string}> $tags
     */
    private function showArticle(array $row, ?string $bodyHtml = null, array $tags = []): Article\Show\Value\Article
    {
        $pdoRow = PdoRow::from(values: $row);

        return new Article\Show\Value\Article(
            id: $pdoRow->int(key: 'id'),
            title: $pdoRow->string(key: 'title'),
            slug: $pdoRow->string(key: 'slug'),
            excerpt: $this->excerpt(row: $pdoRow),
            categoryId: $pdoRow->nullableInt(key: 'category_id'),
            categoryName: $pdoRow->string(key: 'category_name', default: '未分類') ?: '未分類',
            categorySlug: $pdoRow->string(key: 'category_slug'),
            eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
            publishedAt: $pdoRow->date(key: 'published_at'),
            updatedAt: $pdoRow->date(key: 'updated_at'),
            authorName: $pdoRow->string(key: 'author_name', default: '編集部') ?: '編集部',
            bodyHtml: $bodyHtml,
            tags: $tags,
        );
    }

    /** @param array<string, mixed> $row */
    private function homeArticle(array $row): Home\Show\Value\Article
    {
        $pdoRow = PdoRow::from(values: $row);

        return new Home\Show\Value\Article(
            id: $pdoRow->int(key: 'id'),
            title: $pdoRow->string(key: 'title'),
            slug: $pdoRow->string(key: 'slug'),
            excerpt: $this->excerpt(row: $pdoRow),
            categoryId: $pdoRow->nullableInt(key: 'category_id'),
            categoryName: $pdoRow->string(key: 'category_name', default: '未分類') ?: '未分類',
            categorySlug: $pdoRow->string(key: 'category_slug'),
            eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
            publishedAt: $pdoRow->date(key: 'published_at'),
            updatedAt: $pdoRow->date(key: 'updated_at'),
            authorName: $pdoRow->string(key: 'author_name', default: '編集部') ?: '編集部',
        );
    }

    /** @param array<string, mixed> $row */
    private function searchArticle(array $row): Article\Search\Value\Article
    {
        $pdoRow = PdoRow::from(values: $row);

        return new Article\Search\Value\Article(
            id: $pdoRow->int(key: 'id'),
            title: $pdoRow->string(key: 'title'),
            slug: $pdoRow->string(key: 'slug'),
            excerpt: $this->excerpt(row: $pdoRow),
            categoryId: $pdoRow->nullableInt(key: 'category_id'),
            categoryName: $pdoRow->string(key: 'category_name', default: '未分類') ?: '未分類',
            categorySlug: $pdoRow->string(key: 'category_slug'),
            eyecatchPath: $pdoRow->string(key: 'eyecatch_path'),
            publishedAt: $pdoRow->date(key: 'published_at'),
            updatedAt: $pdoRow->date(key: 'updated_at'),
            authorName: $pdoRow->string(key: 'author_name', default: '編集部') ?: '編集部',
        );
    }

    /** @return array<int, array{name:string,slug:string}> */
    private function tags(int $articleId): array
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT t.name, t.slug FROM tags t INNER JOIN article_tags at ON at.tag_id = t.id WHERE at.article_id = :id ORDER BY t.name');
        PdoHelper::execute(statement: $stmt, params: ['id' => $articleId]);

        $tags = [];
        foreach (PdoHelper::fetchAll(statement: $stmt) as $row) {
            $pdoRow = PdoRow::from(values: $row);
            $tags[] = [
                'name' => $pdoRow->string(key: 'name'),
                'slug' => $pdoRow->string(key: 'slug'),
            ];
        }

        return $tags;
    }

    private function excerpt(PdoRow $row): string
    {
        $excerpt = $row->string(key: 'excerpt');
        if ($excerpt !== '') {
            return $excerpt;
        }

        return mb_substr($row->string(key: 'search_body_text'), 0, self::GENERATED_EXCERPT_MAX_LENGTH);
    }

    private function bodyHtml(PdoRow $row): string
    {
        $bodyHtml = $row->string(key: 'rendered_body_html');
        if ($bodyHtml !== '') {
            return $bodyHtml;
        }

        return $this->markdown->bodyToSafeHtml(
            body: $row->string(key: 'body'),
            format: $row->string(key: 'body_format', default: 'markdown'),
        );
    }
}
