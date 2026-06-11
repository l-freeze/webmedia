<?php

declare(strict_types=1);

namespace App\Front\Application\Layout\Value;

final readonly class SiteSettings
{
    public function __construct(
        public string $siteName,
        public string $siteDescription,
        public string $frontTheme,
        public bool $searchEnabled,
        public string $heroImage = '',
    ) {
    }
}
