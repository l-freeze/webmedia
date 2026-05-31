<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Edit\Value;

final readonly class Settings
{
    public function __construct(
        public string $siteName,
        public string $siteDescription,
        public string $contactEmail,
        public string $frontTheme,
        public bool $searchEnabled,
    ) {
    }
}
