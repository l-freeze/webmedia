<?php

declare(strict_types=1);

namespace App\Cms\Setting\Query\GetSettings;

interface SettingRepository
{
    /** @return array<string, string> */
    public function all(): array;

    /** @param array<string, mixed> $data */
    public function save(array $data): void;
}
