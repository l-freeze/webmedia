<?php

declare(strict_types=1);

namespace App\Front\Application\Tag\Show;

final readonly class Input
{
    public function __construct(public string $slug)
    {
    }
}
