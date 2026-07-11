<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\List;

final readonly class Input
{
    private function __construct()
    {
    }

    public static function create(): self
    {
        return new self();
    }
}
