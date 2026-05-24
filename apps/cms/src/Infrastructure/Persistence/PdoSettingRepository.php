<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Persistence;

use App\Cms\Application\Setting\Edit\Query\ISettingQuery;
use App\Cms\Application\Setting\Edit\Value\Settings;
use App\Cms\Domain\Repository\ISettingRepository;
use PDO;

final readonly class PdoSettingRepository implements ISettingQuery, ISettingRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): Settings
    {
        $settings = [];
        foreach ($this->pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        return new Settings(
            siteName: $settings['site_name'] ?? '',
            siteDescription: $settings['site_description'] ?? '',
            contactEmail: $settings['contact_email'] ?? '',
        );
    }

    public function save(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO site_settings (setting_key, setting_value) VALUES (:key, :value) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value');
        foreach (['site_name', 'site_description', 'contact_email'] as $key) {
            $stmt->execute(['key' => $key, 'value' => (string) ($data[$key] ?? '')]);
        }
    }
}
