<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence\Sitemap;

use App\Front\Application\Sitemap\Build\Query\ISitemapEntryQuery;
use App\Front\Application\Sitemap\Build\Value\SitemapEntry;
use App\Front\Infrastructure\Persistence\ArticlePublicationCondition;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoSitemapEntryQuery implements ISitemapEntryQuery
{
    public function __construct(private PDO $pdo, private ArticlePublicationCondition $publication)
    {
    }

    /** @return array<int, SitemapEntry> */
    public function publicEntries(): array
    {
        return [
            new SitemapEntry(path: '/', lastmod: $this->siteLastmod()),
            ...$this->articles(),
            ...$this->pages(),
            ...$this->categories(),
            ...$this->tags(),
        ];
    }

    /** @return array<int, SitemapEntry> */
    private function articles(): array
    {
        $entries = [];
        foreach ($this->fetchPublished(sql: 'SELECT slug, updated_at FROM articles a WHERE ' . $this->publication->sql() . ' ORDER BY published_at DESC, id DESC') as $row) {
            $pdoRow = PdoRow::from(values: $row);
            $entries[] = new SitemapEntry(
                path: '/articles/' . $pdoRow->string(key: 'slug'),
                lastmod: $pdoRow->date(key: 'updated_at', format: 'Y-m-d'),
            );
        }

        return $entries;
    }

    /** @return array<int, SitemapEntry> */
    private function pages(): array
    {
        $entries = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT slug, updated_at FROM pages WHERE status = 'published' ORDER BY display_order, id")) as $row) {
            $pdoRow = PdoRow::from(values: $row);
            $entries[] = new SitemapEntry(
                path: '/pages/' . $pdoRow->string(key: 'slug'),
                lastmod: $pdoRow->date(key: 'updated_at', format: 'Y-m-d'),
            );
        }

        return $entries;
    }

    /** @return array<int, SitemapEntry> */
    private function categories(): array
    {
        $entries = [];
        foreach ($this->fetchPublished(sql: 'SELECT c.slug, MAX(a.updated_at) AS lastmod FROM categories c INNER JOIN articles a ON a.category_id = c.id AND ' . $this->publication->sql() . ' GROUP BY c.id, c.slug ORDER BY c.sort_order, c.name') as $row) {
            $pdoRow = PdoRow::from(values: $row);
            $entries[] = new SitemapEntry(
                path: '/categories/' . $pdoRow->string(key: 'slug'),
                lastmod: $pdoRow->date(key: 'lastmod', format: 'Y-m-d'),
            );
        }

        return $entries;
    }

    /** @return array<int, SitemapEntry> */
    private function tags(): array
    {
        $entries = [];
        foreach ($this->fetchPublished(sql: 'SELECT t.slug, MAX(a.updated_at) AS lastmod FROM tags t INNER JOIN article_tags at ON at.tag_id = t.id INNER JOIN articles a ON a.id = at.article_id AND ' . $this->publication->sql() . ' GROUP BY t.id, t.slug ORDER BY t.name') as $row) {
            $pdoRow = PdoRow::from(values: $row);
            $entries[] = new SitemapEntry(
                path: '/tags/' . $pdoRow->string(key: 'slug'),
                lastmod: $pdoRow->date(key: 'lastmod', format: 'Y-m-d'),
            );
        }

        return $entries;
    }

    private function siteLastmod(): ?string
    {
        $statement = PdoHelper::prepare(pdo: $this->pdo, sql: 'SELECT MAX(updated_at) FROM articles a WHERE ' . $this->publication->sql());
        PdoHelper::execute(statement: $statement, params: $this->publication->parameters());
        $articles = PdoRow::from(values: ['lastmod' => PdoHelper::fetchColumn(statement: $statement)])->nullableString(key: 'lastmod');
        $pages = PdoRow::from(values: ['lastmod' => PdoHelper::fetchColumn(statement: PdoHelper::query(pdo: $this->pdo, sql: "SELECT MAX(updated_at) FROM pages WHERE status = 'published'"))])->nullableString(key: 'lastmod');
        $latest = null;
        foreach ([$articles, $pages] as $candidate) {
            if ($candidate === null || $candidate === '') {
                continue;
            }
            if ($latest === null || $candidate > $latest) {
                $latest = $candidate;
            }
        }
        if ($latest === null) {
            return null;
        }

        $timestamp = strtotime($latest);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchPublished(string $sql): array
    {
        $statement = PdoHelper::prepare(pdo: $this->pdo, sql: $sql);
        PdoHelper::execute(statement: $statement, params: $this->publication->parameters());

        return PdoHelper::fetchAll(statement: $statement);
    }

}
