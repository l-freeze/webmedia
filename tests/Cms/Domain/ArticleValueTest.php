<?php

declare(strict_types=1);

namespace Tests\Cms\Domain;

use App\Cms\Domain\Value\ArticleBodyFormat;
use App\Cms\Domain\Value\ArticleStatus;
use PHPUnit\Framework\TestCase;

final class ArticleValueTest extends TestCase
{
    public function testUnknownArticleStatusIsRejected(): void
    {
        self::assertNotContains('scheduled', array_column(ArticleStatus::cases(), 'value'));
    }

    public function testUnknownArticleBodyFormatIsRejected(): void
    {
        self::assertNotContains('plain', array_column(ArticleBodyFormat::cases(), 'value'));
    }

    public function testRichTextArticleBodyFormatIsAllowed(): void
    {
        self::assertSame('rich_text', ArticleBodyFormat::RichText->value);
    }
}
