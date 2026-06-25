<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List;

final readonly class Input
{
    public function __construct(public int $limit = 100)
    {
    }
}
