<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Delete;

final readonly class Input
{
    public function __construct(public int $id)
    {
    }
}
