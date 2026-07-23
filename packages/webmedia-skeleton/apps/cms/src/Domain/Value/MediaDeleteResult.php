<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

final readonly class MediaDeleteResult
{
    public function __construct(public bool $deleted, public ?string $error = null)
    {
    }
}
