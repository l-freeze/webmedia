<?php

declare(strict_types=1);

namespace Tests\Cms\Infrastructure;

use App\Cms\Domain\Entity\SiteSettings;
use App\Cms\Domain\Value\ContactEmail;
use App\Cms\Domain\Value\FrontTheme;
use App\Cms\Domain\Value\HeroDescription;
use App\Cms\Domain\Value\HeroImagePath;
use App\Cms\Domain\Value\HeroTitle;
use App\Cms\Domain\Value\SearchEnabled;
use App\Cms\Domain\Value\SiteDescription;
use App\Cms\Domain\Value\SiteName;
use App\Cms\Infrastructure\Persistence\PdoSettingRepository;
use App\Shared\Database\PdoHelper;
use PDO;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PdoSettingRepositoryTest extends TestCase
{
    #[DataProvider('storageStateProvider')]
    public function testSaveBindsDisabledSearchAsDatabaseBoolean(bool $existingRow): void
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->exec(<<<'SQL'
CREATE TABLE site_settings (
    site_name TEXT NOT NULL,
    site_description TEXT NOT NULL,
    contact_email TEXT NOT NULL,
    front_theme TEXT NOT NULL,
    search_enabled BOOLEAN NOT NULL,
    hero_image TEXT NOT NULL,
    hero_title TEXT NOT NULL,
    hero_description TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
SQL);
        if ($existingRow) {
            $pdo->exec(<<<'SQL'
INSERT INTO site_settings (
    site_name, site_description, contact_email, front_theme, search_enabled,
    hero_image, hero_title, hero_description, created_at, updated_at
) VALUES (
    'Before', '', '', 'cafe-clean', 1, '', '', '', '2026-01-01 00:00:00', '2026-01-01 00:00:00'
)
SQL);
        }
        $repository = new PdoSettingRepository(pdo: $pdo);

        $repository->save(settings: new SiteSettings(
            siteName: new SiteName(value: 'Example'),
            siteDescription: new SiteDescription(value: 'Example site'),
            contactEmail: new ContactEmail(value: 'owner@example.com'),
            frontTheme: FrontTheme::ExecutiveTrust,
            searchEnabled: new SearchEnabled(value: false),
            heroImage: new HeroImagePath(value: ''),
            heroTitle: new HeroTitle(value: 'Example hero'),
            heroDescription: new HeroDescription(value: 'Example hero description'),
        ));

        $row = PdoHelper::query(
            pdo: $pdo,
            sql: 'SELECT site_name, front_theme, search_enabled FROM site_settings',
        )->fetch(PDO::FETCH_ASSOC);
        self::assertIsArray($row);
        self::assertSame('Example', $row['site_name'] ?? null);
        self::assertSame(FrontTheme::ExecutiveTrust->value, $row['front_theme'] ?? null);
        self::assertSame(0, $row['search_enabled'] ?? null);
    }

    /** @return iterable<string, array{bool}> */
    public static function storageStateProvider(): iterable
    {
        yield 'insert' => [false];
        yield 'update' => [true];
    }
}
