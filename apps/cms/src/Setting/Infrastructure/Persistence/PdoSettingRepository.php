<?php

declare(strict_types=1);

namespace App\Cms\Setting\Infrastructure\Persistence;

use App\Cms\Setting\Query\GetSettings\SettingRepository;
use PDO;

final readonly class PdoSettingRepository implements SettingRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function all(): array
    {
        $settings = [];
        foreach ($this->pdo->query('SELECT setting_key, setting_value FROM site_settings')->fetchAll() as $row) {
            $settings[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
        return $settings;
    }

    public function save(array $data): void
    {
        $stmt = $this->pdo->prepare('REPLACE INTO site_settings (setting_key, setting_value) VALUES (:key, :value)');
        foreach (['site_name', 'site_description', 'contact_email'] as $key) {
            $stmt->execute(['key' => $key, 'value' => (string) ($data[$key] ?? '')]);
        }
    }
}
