<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Update;

final readonly class Output
{
    public function __construct(public bool $updated, public ?string $error = null)
    {
    }
}
