<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Infrastructure\Persistence\PdoArticleRepository;
use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleStatus;
use PHPUnit\Framework\TestCase;

final class ArticleStatusTest extends TestCase
{
    public function testStatusLabelsAreJapanese(): void
    {
        self::assertSame('下書き', PdoArticleRepository::statusLabel(status: 'draft'));
        self::assertSame('公開中', PdoArticleRepository::statusLabel(status: 'published', publishedAt: '2026-05-24 09:00:00', publishedEndsAt: null, now: new \DateTimeImmutable('2026-05-24 10:00:00')));
        self::assertSame('公開予約', PdoArticleRepository::statusLabel(status: 'published', publishedAt: '2026-05-24 11:00:00', publishedEndsAt: null, now: new \DateTimeImmutable('2026-05-24 10:00:00')));
        self::assertSame('公開終了', PdoArticleRepository::statusLabel(status: 'published', publishedAt: '2026-05-24 09:00:00', publishedEndsAt: '2026-05-24 09:30:00', now: new \DateTimeImmutable('2026-05-24 10:00:00')));
        self::assertSame('非表示', PdoArticleRepository::statusLabel(status: 'private'));
        self::assertSame('ゴミ箱', PdoArticleRepository::statusLabel(status: 'trashed'));
    }

    public function testScheduledIsNotPersistedAsArticleStatus(): void
    {
        self::assertSame('draft', (new ArticleStatus(value: 'scheduled'))->value);
    }

    public function testUnknownArticleBodyFormatFallsBackToMarkdown(): void
    {
        self::assertSame('markdown', (new ArticleBodyFormat(value: 'plain'))->value);
    }

    public function testRichTextArticleBodyFormatIsAllowed(): void
    {
        self::assertSame('rich_text', (new ArticleBodyFormat(value: 'rich_text'))->value);
    }
}
