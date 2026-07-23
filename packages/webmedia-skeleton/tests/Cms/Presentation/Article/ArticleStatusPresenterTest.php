<?php

declare(strict_types=1);

namespace Tests\Cms\Presentation\Article;

use App\Cms\Presentation\Presenter\Article\ArticleStatusPresenter;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Shared\Time\FrozenClock;

final class ArticleStatusPresenterTest extends TestCase
{
    public function testStatusLabelsAreJapanese(): void
    {
        $now = new DateTimeImmutable('2026-05-24 10:00:00');
        $presenter = new ArticleStatusPresenter(clock: new FrozenClock($now));

        self::assertSame('下書き', $presenter->label(status: 'draft'));
        self::assertSame('表示（公開日時未設定）', $presenter->label(status: 'published'));
        self::assertSame('公開中', $presenter->label(status: 'published', publishedAt: '2026-05-24 09:00:00'));
        self::assertSame('公開予約', $presenter->label(status: 'published', publishedAt: '2026-05-24 11:00:00'));
        self::assertSame('公開終了', $presenter->label(status: 'published', publishedAt: '2026-05-24 09:00:00', publishedEndsAt: '2026-05-24 09:30:00'));
        self::assertSame('非表示', $presenter->label(status: 'private'));
        self::assertSame('ゴミ箱', $presenter->label(status: 'trashed'));
    }
}
