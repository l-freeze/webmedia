<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Delete;

final readonly class Input
{
    public function __construct(public int $id)
    {
    }
}
