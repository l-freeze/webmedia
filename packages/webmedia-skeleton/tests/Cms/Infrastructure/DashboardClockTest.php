<?php

declare(strict_types=1);

namespace Tests\Cms\Infrastructure;

use App\Cms\Application\Dashboard\Show\Input;
use App\Cms\Application\Dashboard\Show\Interactor;
use App\Cms\Infrastructure\Persistence\PdoDashboardQuery;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Shared\Time\FrozenClock;

final class DashboardClockTest extends TestCase
{
    public function testDashboardPublicationCountsUseApplicationClock(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            self::markTestSkipped('pdo_sqlite is not available.');
        }

        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE articles (id INTEGER PRIMARY KEY, status TEXT NOT NULL, published_at TEXT NULL, published_ends_at TEXT NULL)');
        $pdo->exec('CREATE TABLE inquiries (id INTEGER PRIMARY KEY, status TEXT NOT NULL)');
        $pdo->exec("INSERT INTO articles (id, status, published_at, published_ends_at) VALUES (1, 'published', '2026-07-13 00:29:59', NULL), (2, 'published', '2026-07-13 00:30:00', NULL), (3, 'published', '2026-07-13 00:30:01', NULL), (4, 'published', '2026-07-12 00:00:00', '2026-07-13 00:30:00'), (5, 'draft', NULL, NULL)");
        $pdo->exec("INSERT INTO inquiries (id, status) VALUES (1, 'new'), (2, 'closed')");

        $output = (new Interactor(
            dashboard: new PdoDashboardQuery($pdo),
            clock: new FrozenClock(new DateTimeImmutable('2026-07-13 00:30:00 UTC')),
        ))->execute(Input::create());

        self::assertSame(2, $output->stats->published);
        self::assertSame(1, $output->stats->scheduled);
        self::assertSame(1, $output->stats->draft);
        self::assertSame(1, $output->stats->inquiries);
    }
}
