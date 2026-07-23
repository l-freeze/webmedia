<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\List;

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
