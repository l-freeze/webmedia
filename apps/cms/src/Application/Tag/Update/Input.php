<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Update;

final readonly class Input
{
    /** @param array<string, mixed> $data */
    public function __construct(public int $id, public array $data)
    {
    }
}
