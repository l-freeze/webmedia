<?php

declare(strict_types=1);

namespace Tests\Shared\Markdown;

use App\Shared\Markdown\Infrastructure\Markdown;
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

    public function testRichTextJsonBodyIsConvertedToSafeHtml(): void
    {
        $markdown = new Markdown();
        $body = json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'heading', 'attrs' => ['level' => 1], 'content' => [['type' => 'text', 'text' => '見出し']]],
                ['type' => 'paragraph', 'content' => [
                    ['type' => 'text', 'text' => '本文'],
                    ['type' => 'text', 'text' => 'リンク', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]],
                ]],
            ],
        ], JSON_THROW_ON_ERROR);

        $html = $markdown->bodyToSafeHtml(body: $body, format: 'rich_text');

        self::assertStringContainsString('<h1>見出し</h1>', $html);
        self::assertStringContainsString('<p>本文リンク</p>', $html);
        self::assertStringNotContainsString('javascript:', $html);
    }
}
