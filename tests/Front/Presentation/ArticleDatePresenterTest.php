<?php

declare(strict_types=1);

namespace Tests\Front\Presentation;

use App\Front\Presentation\Presenter\ArticleDatePresenter;
use PHPUnit\Framework\TestCase;

final class ArticleDatePresenterTest extends TestCase
{
    public function testPresentsStoredUtcArticleDatesInJst(): void
    {
        $article = (new ArticleDatePresenter())->present([
            'published_at' => '2026-07-12 15:30:00',
            'updated_at' => '2026-07-13 14:59:59',
        ]);

        self::assertSame('2026-07-13', $article['published_at']);
        self::assertSame('2026-07-13', $article['updated_at']);
    }
}
