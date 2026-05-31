<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\Update;

final readonly class Input
{
    /** @param array<string, mixed> $data */
    public function __construct(public string $type, public int $id, public array $data)
    {
    }
}
