<?php

declare(strict_types=1);

namespace Tests\Cms\Application\Article;

use App\Cms\Application\Article\PublicationDateTime;
use PHPUnit\Framework\TestCase;

final class PublicationDateTimeTest extends TestCase
{
    public function testConvertsBetweenJstEditorValueAndUtcStorageValue(): void
    {
        $publicationDateTime = new PublicationDateTime();

        self::assertSame('2026-07-13 00:30:00', $publicationDateTime->toStorage('2026-07-13 09:30:00'));
        self::assertSame('2026-07-13 09:30:00', $publicationDateTime->toEditor('2026-07-13 00:30:00'));
    }

    public function testValidatesExactEditorFormat(): void
    {
        self::assertTrue(PublicationDateTime::isValidEditorValue(''));
        self::assertTrue(PublicationDateTime::isValidEditorValue('2026-07-13 09:30:00'));
        self::assertFalse(PublicationDateTime::isValidEditorValue('2026-02-30 09:30:00'));
        self::assertFalse(PublicationDateTime::isValidEditorValue('2026-07-13T09:30'));
    }
}
