<?php

declare(strict_types=1);

namespace App\Cms\Domain\Entity;

final readonly class SiteSettings
{
    public const DEFAULT_FRONT_THEME = 'cafe-clean';
    public const DEFAULT_HERO_TITLE = '読みやすく、探しやすいWEBメディア';
    public const DEFAULT_HERO_DESCRIPTION = '小規模メディア運営に必要な記事、カテゴリ、タグ、検索導線を整理しています。';

    /** @var list<string> */
    public const FRONT_THEMES = ['cafe-clean', 'tech-edge', 'executive-trust', 'dev-docs', 'recipe-fresh', 'pop-girly', 'mama-soft'];

    public function __construct(
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
}
