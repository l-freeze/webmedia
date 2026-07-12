<?php

declare(strict_types=1);

namespace Tests\Front\Infrastructure;

use App\Front\Infrastructure\Persistence\PdoArticleQuery;
use App\Front\Infrastructure\Persistence\ArticlePublicationCondition;
use App\Shared\Markdown\Infrastructure\Markdown;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Shared\Time\FrozenClock;

final class PublicationClockTest extends TestCase
{
    public function testArticleVisibilityUsesInjectedClockInsteadOfDatabaseCurrentTime(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite is not available.');
        }

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE categories (id INTEGER PRIMARY KEY, name TEXT NOT NULL, slug TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE articles (id INTEGER PRIMARY KEY, title TEXT NOT NULL, slug TEXT NOT NULL, body TEXT NOT NULL, body_format TEXT NOT NULL, rendered_body_html TEXT NULL, search_body_text TEXT NULL, excerpt TEXT NULL, status TEXT NOT NULL, category_id INTEGER NULL, eyecatch_path TEXT NULL, published_at TEXT NULL, published_ends_at TEXT NULL, updated_at TEXT NOT NULL, author_name TEXT NOT NULL)');
        $pdo->exec("INSERT INTO categories (id, name, slug) VALUES (1, 'Category', 'category')");

        $insert = $pdo->prepare('INSERT INTO articles (id, title, slug, body, body_format, excerpt, status, category_id, published_at, published_ends_at, updated_at, author_name) VALUES (:id, :title, :slug, :body, :body_format, :excerpt, :status, :category_id, :published_at, :published_ends_at, :updated_at, :author_name)');
        $base = [
            'title' => 'Article',
            'body' => 'Body',
            'body_format' => 'markdown',
            'excerpt' => 'Excerpt',
            'status' => 'published',
            'category_id' => 1,
            'published_ends_at' => null,
            'updated_at' => '2026-07-13 00:00:00',
            'author_name' => 'Editor',
        ];
        $insert->execute([...$base, 'id' => 1, 'slug' => 'already-public', 'published_at' => '2026-07-13 00:29:59']);
        $insert->execute([...$base, 'id' => 2, 'slug' => 'starts-now', 'published_at' => '2026-07-13 00:30:00']);
        $insert->execute([...$base, 'id' => 3, 'slug' => 'scheduled', 'published_at' => '2026-07-13 00:30:01']);
        $insert->execute([...$base, 'id' => 4, 'slug' => 'ended-now', 'published_at' => '2026-07-12 00:00:00', 'published_ends_at' => '2026-07-13 00:30:00']);

        $query = new PdoArticleQuery(
            pdo: $pdo,
            markdown: new Markdown(),
            publication: new ArticlePublicationCondition(
                clock: new FrozenClock(new DateTimeImmutable('2026-07-13 00:30:00 UTC')),
            ),
        );

        self::assertSame(2, $query->countPublished()->value);
        self::assertSame(['starts-now', 'already-public'], array_map(
            static fn ($article): string => $article->slug,
            $query->latest(limit: 10, offset: 0),
        ));
    }
}
