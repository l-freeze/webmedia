<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\Show;

final readonly class Input
{
    public function __construct(public string $slug)
    {
    }
}
