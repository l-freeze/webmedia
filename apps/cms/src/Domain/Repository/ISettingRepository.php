<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

interface ISettingRepository
{
    /** @param array<string, mixed> $data */
    public function save(array $data): void;
}
