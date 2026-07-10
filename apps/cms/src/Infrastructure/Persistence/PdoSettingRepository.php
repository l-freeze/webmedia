<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Setting\Edit\Query\ISettingQuery;
use App\Cms\Application\Setting\Edit\Value\Settings;
use App\Cms\Domain\Repository\ISettingRepository;
use App\Shared\Database\PdoHelper;
use App\Shared\Database\PdoRow;
use PDO;

final readonly class PdoSettingRepository implements ISettingQuery, ISettingRepository
{
    private const DEFAULT_FRONT_THEME = 'cafe-clean';
    private const DEFAULT_HERO_TITLE = '読みやすく、探しやすいWEBメディア';
    private const DEFAULT_HERO_DESCRIPTION = '小規模メディア運営に必要な記事、カテゴリ、タグ、検索導線を整理しています。';

    /** @var list<string> */
    private const FRONT_THEMES = ['cafe-clean', 'tech-edge', 'executive-trust', 'dev-docs', 'recipe-fresh', 'pop-girly', 'mama-soft'];

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
                frontTheme: self::DEFAULT_FRONT_THEME,
                searchEnabled: true,
                heroImage: '',
                heroTitle: self::DEFAULT_HERO_TITLE,
                heroDescription: self::DEFAULT_HERO_DESCRIPTION,
            );
        }
        $settings = PdoRow::from(values: $row);

        $frontTheme = $settings->string(key: 'front_theme', default: self::DEFAULT_FRONT_THEME);
        if (!in_array($frontTheme, self::FRONT_THEMES, true)) {
            $frontTheme = self::DEFAULT_FRONT_THEME;
        }

        return new Settings(
            siteName: $settings->string(key: 'site_name'),
            siteDescription: $settings->string(key: 'site_description'),
            contactEmail: $settings->string(key: 'contact_email'),
            frontTheme: $frontTheme,
            searchEnabled: self::boolValue(value: $row['search_enabled'] ?? null, default: true),
            heroImage: $settings->string(key: 'hero_image'),
            heroTitle: self::settingOrDefault(value: $settings->nullableString(key: 'hero_title'), default: self::DEFAULT_HERO_TITLE),
            heroDescription: self::settingOrDefault(value: $settings->nullableString(key: 'hero_description'), default: self::DEFAULT_HERO_DESCRIPTION),
        );
    }

    public function save(array $data): void
    {
        $input = PdoRow::from(values: $data);
        $frontTheme = $input->string(key: 'front_theme');
        if (!in_array($frontTheme, self::FRONT_THEMES, true)) {
            $frontTheme = self::DEFAULT_FRONT_THEME;
        }
        $searchEnabled = $input->string(key: 'search_enabled', default: '1') === '1';
        $now = date('Y-m-d H:i:s');

        $params = [
            'site_name' => $input->string(key: 'site_name'),
            'site_description' => $input->string(key: 'site_description'),
            'contact_email' => $input->string(key: 'contact_email'),
            'front_theme' => $frontTheme,
            'search_enabled' => $searchEnabled,
            'hero_image' => $input->string(key: 'hero_image'),
            'hero_title' => $input->string(key: 'hero_title'),
            'hero_description' => $input->string(key: 'hero_description'),
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
