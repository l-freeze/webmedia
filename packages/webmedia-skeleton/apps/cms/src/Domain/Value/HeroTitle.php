<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class HeroTitle
{
    public const DEFAULT = '読みやすく、探しやすいWEBメディア';

    public function __construct(public string $value)
    {
    }
}
