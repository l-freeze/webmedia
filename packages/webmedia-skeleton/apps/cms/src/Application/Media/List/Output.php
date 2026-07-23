<?php

declare(strict_types=1);

namespace App\Cms\Application\Media\List;

use App\Cms\Application\Media\List\Value\Media;

final readonly class Output
{
    /** @param array<int, Media> $rows */
    public function __construct(public array $rows)
    {
    }
}
