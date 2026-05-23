<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\List;

final readonly class Input
{
    public function __construct(public string $keyword)
    {
    }
}
