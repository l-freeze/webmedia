<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Setting\Edit\Query\ISettingQuery;
use App\Cms\Application\Setting\Edit\Value\Settings;
use App\Cms\Domain\Repository\ISettingRepository;
use App\Framework\Database\PdoHelper;
use PDO;

final readonly class PdoSettingRepository implements ISettingQuery, ISettingRepository
{
    private const DEFAULT_FRONT_THEME = 'cafe-clean';

    /** @var list<string> */
    private const FRONT_THEMES = ['cafe-clean', 'tech-edge', 'executive-trust'];

    public function __construct(private PDO $pdo)
    {
    }

    public function all(): Settings
    {
        $settings = [];
        foreach (PdoHelper::fetchAll(statement: PdoHelper::query(pdo: $this->pdo, sql: 'SELECT setting_key, setting_value FROM site_settings')) as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        $frontTheme = $settings['front_theme'] ?? self::DEFAULT_FRONT_THEME;
        if (!in_array($frontTheme, self::FRONT_THEMES, true)) {
            $frontTheme = self::DEFAULT_FRONT_THEME;
        }

        return new Settings(
            siteName: $settings['site_name'] ?? '',
            siteDescription: $settings['site_description'] ?? '',
            contactEmail: $settings['contact_email'] ?? '',
            frontTheme: $frontTheme,
            searchEnabled: ($settings['search_enabled'] ?? '1') === '1',
        );
    }

    public function save(array $data): void
    {
        $stmt = PdoHelper::prepare(pdo: $this->pdo, sql: 'INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value');
        foreach (['site_name', 'site_description', 'contact_email', 'front_theme', 'search_enabled'] as $key) {
            $value = (string) ($data[$key] ?? '');
            if ($key === 'front_theme' && !in_array($value, self::FRONT_THEMES, true)) {
                $value = self::DEFAULT_FRONT_THEME;
            }
            if ($key === 'search_enabled' && !in_array($value, ['0', '1'], true)) {
                $value = '1';
            }

            PdoHelper::execute(statement: $stmt, params: ['key' => $key, 'value' => $value]);
        }
    }
}
