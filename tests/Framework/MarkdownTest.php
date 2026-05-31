<?php

declare(strict_types=1);

namespace Tests\Framework;

use App\Framework\Content\Markdown;
use PHPUnit\Framework\TestCase;

final class MarkdownTest extends TestCase
{
    public function testMarkdownBodyIsConvertedToSafeHtml(): void
    {
        $markdown = new Markdown();

        $html = $markdown->bodyToSafeHtml(body: '## 見出し', format: 'markdown');

        self::assertStringContainsString('<h2>見出し</h2>', $html);
    }

    public function testHtmlBodyIsPurifiedWithoutMarkdownConversion(): void
    {
        $markdown = new Markdown();

        $html = $markdown->bodyToSafeHtml(body: '<h2>見出し</h2><script>alert(1)</script>', format: 'html');

        self::assertStringContainsString('<h2>見出し</h2>', $html);
        self::assertStringNotContainsString('<script>', $html);
    }
}
