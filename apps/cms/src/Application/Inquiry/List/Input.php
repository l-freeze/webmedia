<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\List;

final readonly class Input
{
    public function __construct(public int $page = 1)
    {
    }
}
