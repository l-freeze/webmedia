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
        public string $heroImage = '',
        public string $heroLabel = '新着記事',
        public string $heroTitle = '読みやすく、探しやすいWEBメディア',
        public string $heroDescription = '小規模メディア運営に必要な記事、カテゴリ、タグ、検索導線を整理しています。',
    ) {
    }
}
