<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

use App\Cms\Domain\Value\ContactEmail;
use App\Cms\Domain\Value\FrontTheme;
use App\Cms\Domain\Value\HeroDescription;
use App\Cms\Domain\Value\HeroImagePath;
use App\Cms\Domain\Value\HeroTitle;
use App\Cms\Domain\Value\SearchEnabled;
use App\Cms\Domain\Value\SiteDescription;
use App\Cms\Domain\Value\SiteName;

final readonly class SiteSettings
{
    public function __construct(
        public SiteName $siteName,
        public SiteDescription $siteDescription,
        public ContactEmail $contactEmail,
        public FrontTheme $frontTheme,
        public SearchEnabled $searchEnabled,
        public HeroImagePath $heroImage,
        public HeroTitle $heroTitle,
        public HeroDescription $heroDescription,
    ) {
    }
}
