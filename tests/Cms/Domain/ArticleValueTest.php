<?php

declare(strict_types=1);

namespace Tests\Cms\Domain;

use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleStatus;
use PHPUnit\Framework\TestCase;

final class ArticleValueTest extends TestCase
{
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
