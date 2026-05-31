<?php

declare(strict_types=1);

namespace App\Front\Infrastructure\Persistence;

use App\Framework\Database\PdoHelper;
use App\Front\Application\Layout\Query\ISiteSettingQuery;
use App\Front\Application\Layout\Value\SiteSettings;
use PDO;

final readonly class PdoSiteSettingQuery implements ISiteSettingQuery
{
    private const DEFAULT_THEME = 'cafe-clean';

    /** @var list<string> */
    private const THEMES = ['cafe-clean', 'tech-edge', 'executive-trust'];

    public function __construct(private PDO $pdo)
    {
    }

    public function current(): SiteSettings
    {
        $settings = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT setting_key, setting_value FROM site_settings')) as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }

        $frontTheme = $settings['front_theme'] ?? self::DEFAULT_THEME;
        if (!in_array($frontTheme, self::THEMES, true)) {
            $frontTheme = self::DEFAULT_THEME;
        }

        return new SiteSettings(
            siteName: $settings['site_name'] ?? 'WEB Media',
            siteDescription: $settings['site_description'] ?? '',
            frontTheme: $frontTheme,
            searchEnabled: ($settings['search_enabled'] ?? '1') === '1',
        );
    }
}
