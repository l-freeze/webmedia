<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

use App\Cms\Domain\Entity\SiteSettings;

interface ISettingRepository
{
    public function save(SiteSettings $settings): void;
}
