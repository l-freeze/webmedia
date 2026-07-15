<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class HeroDescription
{
    public const DEFAULT = '小規模メディア運営に必要な記事、カテゴリ、タグ、検索導線を整理しています。';

    public function __construct(public string $value)
    {
    }
}
