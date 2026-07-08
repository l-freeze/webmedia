<?php

declare(strict_types=1);

namespace App\FrontBusiness\Infrastructure\Persistence;

use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use App\FrontBusiness\Application\Layout\Load\Query\ISiteSettingQuery;
use App\FrontBusiness\Application\Layout\Load\Value\SiteSettings;
use PDO;

final readonly class PdoSiteSettingQuery implements ISiteSettingQuery
{
    private const DEFAULT_SITE_NAME = '業務システム設計室';
    private const DEFAULT_SITE_DESCRIPTION = '管理画面、権限、監査ログ、履歴、状態変更を整理し、後から説明できる業務システムを設計します。';
    private const DEFAULT_THEME = 'business-architecture';
    private const DEFAULT_HERO_TITLE = '業務システム・管理画面の権限、監査、履歴を設計します';
    private const DEFAULT_HERO_DESCRIPTION = '安く作ることより、後で壊れない責任境界を先に決める。初期リスクレビューと設計・実装で、説明できる管理画面を作ります。';

    /** @var list<string> */
    private const THEMES = ['business-architecture', 'cafe-clean', 'tech-edge', 'executive-trust', 'dev-docs', 'recipe-fresh', 'pop-girly', 'mama-soft'];

    public function __construct(private PDO $pdo)
    {
    }

    public function current(): SiteSettings
    {
        $settings = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT setting_key, setting_value FROM site_settings')) as $row) {
            $pdoRow = PdoRow::from(values: $row);
            $settings[$pdoRow->string(key: 'setting_key')] = $pdoRow->string(key: 'setting_value');
        }

        $frontTheme = $settings['front_business_theme'] ?? self::DEFAULT_THEME;
        if (!in_array($frontTheme, self::THEMES, true)) {
            $frontTheme = self::DEFAULT_THEME;
        }

        return new SiteSettings(
            siteName: self::settingOrDefault(value: $settings['front_business_site_name'] ?? null, default: self::DEFAULT_SITE_NAME),
            siteDescription: self::settingOrDefault(value: $settings['front_business_site_description'] ?? null, default: self::DEFAULT_SITE_DESCRIPTION),
            frontTheme: $frontTheme,
            searchEnabled: ($settings['front_business_search_enabled'] ?? '0') === '1',
            heroImage: $settings['front_business_hero_image'] ?? '',
            heroTitle: self::settingOrDefault(value: $settings['front_business_hero_title'] ?? null, default: self::DEFAULT_HERO_TITLE),
            heroDescription: self::settingOrDefault(value: $settings['front_business_hero_description'] ?? null, default: self::DEFAULT_HERO_DESCRIPTION),
        );
    }

    private static function settingOrDefault(string|null $value, string $default): string
    {
        if ($value === null || trim($value) === '') {
            return $default;
        }

        return $value;
    }
}
