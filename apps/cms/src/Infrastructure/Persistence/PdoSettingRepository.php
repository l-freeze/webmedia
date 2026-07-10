<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Setting\Edit\Query\ISettingQuery;
use App\Cms\Application\Setting\Edit\Value\Settings;
use App\Cms\Domain\Entity\SiteSettings;
use App\Cms\Domain\Repository\ISettingRepository;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoSettingRepository implements ISettingQuery, ISettingRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): Settings
    {
        $row = PdoHelper::fetch(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT site_name, site_description, contact_email, front_theme, search_enabled, hero_image, hero_title, hero_description FROM site_settings LIMIT 1'));
        if ($row === null) {
            return new Settings(
                siteName: '',
                siteDescription: '',
                contactEmail: '',
                frontTheme: SiteSettings::DEFAULT_FRONT_THEME,
                searchEnabled: true,
                heroImage: '',
                heroTitle: SiteSettings::DEFAULT_HERO_TITLE,
                heroDescription: SiteSettings::DEFAULT_HERO_DESCRIPTION,
            );
        }
        $settings = PdoRow::from(values: $row);

        $frontTheme = $settings->string(key: 'front_theme', default: SiteSettings::DEFAULT_FRONT_THEME);
        if (!in_array($frontTheme, SiteSettings::FRONT_THEMES, true)) {
            $frontTheme = SiteSettings::DEFAULT_FRONT_THEME;
        }

        return new Settings(
            siteName: $settings->string(key: 'site_name'),
            siteDescription: $settings->string(key: 'site_description'),
            contactEmail: $settings->string(key: 'contact_email'),
            frontTheme: $frontTheme,
            searchEnabled: self::boolValue(value: $row['search_enabled'] ?? null, default: true),
            heroImage: $settings->string(key: 'hero_image'),
            heroTitle: self::settingOrDefault(value: $settings->nullableString(key: 'hero_title'), default: SiteSettings::DEFAULT_HERO_TITLE),
            heroDescription: self::settingOrDefault(value: $settings->nullableString(key: 'hero_description'), default: SiteSettings::DEFAULT_HERO_DESCRIPTION),
        );
    }

    public function save(SiteSettings $settings): void
    {
        $now = date('Y-m-d H:i:s');

        $params = [
            'site_name' => $settings->siteName,
            'site_description' => $settings->siteDescription,
            'contact_email' => $settings->contactEmail,
            'front_theme' => $settings->frontTheme,
            'search_enabled' => $settings->searchEnabled,
            'hero_image' => $settings->heroImage,
            'hero_title' => $settings->heroTitle,
            'hero_description' => $settings->heroDescription,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $update = PdoHelper::prepare(pdo: $this->pdo, sql: '
            UPDATE site_settings SET
                site_name = :site_name,
                site_description = :site_description,
                contact_email = :contact_email,
                front_theme = :front_theme,
                search_enabled = :search_enabled,
                hero_image = :hero_image,
                hero_title = :hero_title,
                hero_description = :hero_description,
                updated_at = :updated_at
        ');
        PdoHelper::execute(statement: $update, params: [
            'site_name' => $params['site_name'],
            'site_description' => $params['site_description'],
            'contact_email' => $params['contact_email'],
            'front_theme' => $params['front_theme'],
            'search_enabled' => $params['search_enabled'],
            'hero_image' => $params['hero_image'],
            'hero_title' => $params['hero_title'],
            'hero_description' => $params['hero_description'],
            'updated_at' => $params['updated_at'],
        ]);
        if ($update->rowCount() > 0) {
            return;
        }

        $insert = PdoHelper::prepare(pdo: $this->pdo, sql: '
            INSERT INTO site_settings (
                site_name, site_description, contact_email, front_theme, search_enabled,
                hero_image, hero_title, hero_description, created_at, updated_at
            ) VALUES (
                :site_name, :site_description, :contact_email, :front_theme, :search_enabled,
                :hero_image, :hero_title, :hero_description, :created_at, :updated_at
            )
        ');
        PdoHelper::execute(statement: $insert, params: $params);
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
