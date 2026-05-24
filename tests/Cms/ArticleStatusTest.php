<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Infrastructure\Persistence\PdoArticleRepository;
use App\Cms\Domain\Value\ArticleStatus;
use PHPUnit\Framework\TestCase;

final class ArticleStatusTest extends TestCase
{
    public function testStatusLabelsAreJapanese(): void
    {
        self::assertSame('下書き', PdoArticleRepository::statusLabel('draft'));
        self::assertSame('公開中', PdoArticleRepository::statusLabel('published', '2026-05-24 09:00:00', null, new \DateTimeImmutable('2026-05-24 10:00:00')));
        self::assertSame('公開予約', PdoArticleRepository::statusLabel('published', '2026-05-24 11:00:00', null, new \DateTimeImmutable('2026-05-24 10:00:00')));
        self::assertSame('公開終了', PdoArticleRepository::statusLabel('published', '2026-05-24 09:00:00', '2026-05-24 09:30:00', new \DateTimeImmutable('2026-05-24 10:00:00')));
        self::assertSame('非表示', PdoArticleRepository::statusLabel('private'));
        self::assertSame('ゴミ箱', PdoArticleRepository::statusLabel('trashed'));
    }

    public function testScheduledIsNotPersistedAsArticleStatus(): void
    {
        self::assertSame('draft', (new ArticleStatus('scheduled'))->value);
    }
}
