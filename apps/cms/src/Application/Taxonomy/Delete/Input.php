<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\Delete;

final readonly class Input
{
    public function __construct(public string $type, public int $id)
    {
    }
}
