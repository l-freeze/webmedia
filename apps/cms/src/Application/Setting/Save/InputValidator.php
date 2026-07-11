<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Save;

use App\Cms\Domain\Entity\SiteSettings;

final readonly class InputValidator
{
    public static function siteSettings(Input $input): SiteSettings
    {
        $frontTheme = in_array($input->frontTheme, SiteSettings::FRONT_THEMES, true)
            ? $input->frontTheme
            : SiteSettings::DEFAULT_FRONT_THEME;

        return new SiteSettings(
            siteName: $input->siteName,
            siteDescription: $input->siteDescription,
            contactEmail: $input->contactEmail,
            frontTheme: $frontTheme,
            searchEnabled: $input->searchEnabled,
            heroImage: $input->heroImage,
            heroTitle: $input->heroTitle,
            heroDescription: $input->heroDescription,
        );
    }
}
