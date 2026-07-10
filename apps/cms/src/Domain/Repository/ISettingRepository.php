<?php

declare(strict_types=1);

namespace App\Cms\Domain\Repository;

interface ISettingRepository
{
    /**
     * @param array{
     *     site_name?:mixed,
     *     site_description?:mixed,
     *     contact_email?:mixed,
     *     front_theme?:mixed,
     *     search_enabled?:mixed,
     *     hero_image?:mixed,
     *     hero_title?:mixed,
     *     hero_description?:mixed
     * } $data
     */
    public function save(array $data): void;
}
