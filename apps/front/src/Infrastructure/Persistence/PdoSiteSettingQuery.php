<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use App\Front\Application\Layout\Load\Query\ISiteSettingQuery;
use App\Front\Application\Layout\Load\Value\SiteSettings;
use PDO;

final readonly class PdoSiteSettingQuery implements ISiteSettingQuery
{
    private const DEFAULT_THEME = 'cafe-clean';
    private const DEFAULT_HERO_TITLE = '読みやすく、探しやすいWEBメディア';
    private const DEFAULT_HERO_DESCRIPTION = '小規模メディア運営に必要な記事、カテゴリ、タグ、検索導線を整理しています。';

    /** @var list<string> */
    private const THEMES = ['cafe-clean', 'tech-edge', 'executive-trust', 'dev-docs', 'recipe-fresh', 'pop-girly', 'mama-soft'];

    public function __construct(private PDO $pdo)
    {
    }

    public function current(): SiteSettings
    {
        $row = PdoHelper::fetch(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT site_name, site_description, front_theme, search_enabled, hero_image, hero_title, hero_description FROM site_settings LIMIT 1'));
        if ($row === null) {
            return new SiteSettings(
                siteName: 'WEB Media',
                siteDescription: '',
                frontTheme: self::DEFAULT_THEME,
                searchEnabled: true,
                heroImage: '',
                heroTitle: self::DEFAULT_HERO_TITLE,
                heroDescription: self::DEFAULT_HERO_DESCRIPTION,
            );
        }
        $settings = PdoRow::from(values: $row);

        $frontTheme = $settings->string(key: 'front_theme', default: self::DEFAULT_THEME);
        if (!in_array($frontTheme, self::THEMES, true)) {
            $frontTheme = self::DEFAULT_THEME;
        }

        return new SiteSettings(
            siteName: $settings->string(key: 'site_name', default: 'WEB Media'),
            siteDescription: $settings->string(key: 'site_description'),
            frontTheme: $frontTheme,
            searchEnabled: self::boolValue(value: $row['search_enabled'] ?? null, default: true),
            heroImage: $settings->string(key: 'hero_image'),
            heroTitle: self::settingOrDefault(value: $settings->nullableString(key: 'hero_title'), default: self::DEFAULT_HERO_TITLE),
            heroDescription: self::settingOrDefault(value: $settings->nullableString(key: 'hero_description'), default: self::DEFAULT_HERO_DESCRIPTION),
        );
    }

    private static function boolValue(mixed $value, bool $default): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['1', 'true', 't', 'yes', 'on'], true);
        }

        return $default;
    }

    private static function settingOrDefault(string|null $value, string $default): string
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }

        return $value;
    }
}
