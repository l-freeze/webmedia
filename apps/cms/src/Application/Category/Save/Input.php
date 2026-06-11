<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Save;

final readonly class Input
{
    /** @param array<string, mixed> $data */
    public function __construct(public array $data)
    {
    }
}
