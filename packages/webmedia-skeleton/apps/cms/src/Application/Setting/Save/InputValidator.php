<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Save;

use App\Cms\Domain\Entity\SiteSettings;
use App\Cms\Domain\Value\ContactEmail;
use App\Cms\Domain\Value\FrontTheme;
use App\Cms\Domain\Value\HeroDescription;
use App\Cms\Domain\Value\HeroImagePath;
use App\Cms\Domain\Value\HeroTitle;
use App\Cms\Domain\Value\SearchEnabled;
use App\Cms\Domain\Value\SiteDescription;
use App\Cms\Domain\Value\SiteName;

final readonly class InputValidator
{
    public static function siteSettings(Input $input): SiteSettings
    {
        $frontTheme = FrontTheme::tryFrom($input->frontTheme) ?? FrontTheme::CafeClean;

        return new SiteSettings(
            siteName: new SiteName(value: $input->siteName),
            siteDescription: new SiteDescription(value: $input->siteDescription),
            contactEmail: new ContactEmail(value: $input->contactEmail),
            frontTheme: $frontTheme,
            searchEnabled: new SearchEnabled(value: $input->searchEnabled),
            heroImage: new HeroImagePath(value: $input->heroImage),
            heroTitle: new HeroTitle(value: $input->heroTitle),
            heroDescription: new HeroDescription(value: $input->heroDescription),
        );
    }
}
