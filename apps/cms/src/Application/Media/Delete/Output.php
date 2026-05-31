<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\Delete;

final readonly class Output
{
    public function __construct(public bool $deleted, public ?string $error = null)
    {
    }
}
