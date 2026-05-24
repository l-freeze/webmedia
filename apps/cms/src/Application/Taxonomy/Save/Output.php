<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\Save;

final readonly class Output
{
    public function __construct(public bool $saved, public ?string $error = null)
    {
    }
}
