<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Save;

final readonly class Input
{
    private function __construct(
        public string $siteName,
        public string $siteDescription,
        public string $contactEmail,
        public string $frontTheme,
        public bool $searchEnabled,
        public string $heroImage,
        public string $heroTitle,
        public string $heroDescription,
    ) {
    }

    public static function create(
        string $siteName,
        string $siteDescription,
        string $contactEmail,
        string $frontTheme,
        bool $searchEnabled,
        string $heroImage,
        string $heroTitle,
        string $heroDescription,
    ): self {
        return new self(
            siteName: trim($siteName),
            siteDescription: trim($siteDescription),
            contactEmail: trim($contactEmail),
            frontTheme: trim($frontTheme),
            searchEnabled: $searchEnabled,
            heroImage: trim($heroImage),
            heroTitle: trim($heroTitle),
            heroDescription: trim($heroDescription),
        );
    }
}
