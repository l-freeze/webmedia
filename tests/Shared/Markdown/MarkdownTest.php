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
                    ['type' => 'text', 'text' => '下線', 'marks' => [['type' => 'underline']]],
                    ['type' => 'text', 'text' => 'リンク', 'marks' => [['type' => 'link', 'attrs' => ['href' => 'javascript:alert(1)']]]],
                ]],
            ],
        ], JSON_THROW_ON_ERROR);

        $html = $markdown->bodyToSafeHtml(body: $body, format: 'rich_text');

        self::assertStringContainsString('<h1>見出し</h1>', $html);
        self::assertStringContainsString('<p>本文<u>下線</u>リンク</p>', $html);
        self::assertStringNotContainsString('javascript:', $html);
    }

    public function testRichTextJsonTableRuleAndImageAreConvertedToSafeHtml(): void
    {
        $markdown = new Markdown();
        $paragraph = static fn (string $text): array => [
            'type' => 'paragraph',
            'content' => [['type' => 'text', 'text' => $text]],
        ];
        $body = json_encode([
            'type' => 'doc',
            'content' => [
                ['type' => 'horizontalRule'],
                ['type' => 'image', 'attrs' => ['src' => '/uploads/media/sample.webp', 'alt' => 'sample']],
                ['type' => 'table', 'content' => [
                    ['type' => 'tableRow', 'content' => [
                        ['type' => 'tableHeader', 'content' => [$paragraph('銘柄')]],
                        ['type' => 'tableHeader', 'content' => [$paragraph('評価')]],
                    ]],
                    ['type' => 'tableRow', 'content' => [
                        ['type' => 'tableCell', 'content' => [$paragraph('山崎')]],
                        ['type' => 'tableCell', 'content' => [$paragraph('5')]],
                    ]],
                ]],
            ],
        ], JSON_THROW_ON_ERROR);

        $html = $markdown->bodyToSafeHtml(body: $body, format: 'rich_text');

        self::assertStringContainsString('<hr', $html);
        self::assertStringContainsString('<img src="/uploads/media/sample.webp" alt="sample"', $html);
        self::assertStringContainsString('<table>', $html);
        self::assertStringContainsString('<th><p>銘柄</p></th>', $html);
        self::assertStringContainsString('<td><p>山崎</p></td>', $html);
    }
}
