<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\List;

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
