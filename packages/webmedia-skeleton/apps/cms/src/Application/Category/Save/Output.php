<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Save;

final readonly class Output
{
    public function __construct(public bool $saved, public ?string $error = null)
    {
    }
}
