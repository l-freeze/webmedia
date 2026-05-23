<?php

declare(strict_types=1);

namespace Tests\Cms;

use App\Cms\Infrastructure\Persistence\PdoArticleRepository;
use PHPUnit\Framework\TestCase;

final class ArticleStatusTest extends TestCase
{
    public function testStatusLabelsAreJapanese(): void
    {
        self::assertSame('下書き', PdoArticleRepository::statusLabel('draft'));
        self::assertSame('公開中', PdoArticleRepository::statusLabel('published'));
        self::assertSame('公開予約', PdoArticleRepository::statusLabel('scheduled'));
        self::assertSame('非公開', PdoArticleRepository::statusLabel('private'));
        self::assertSame('ゴミ箱', PdoArticleRepository::statusLabel('trashed'));
    }
}
