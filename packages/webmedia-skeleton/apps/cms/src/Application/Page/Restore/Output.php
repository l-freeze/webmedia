<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Restore;

final readonly class Output
{
    public function __construct(public bool $restored)
    {
    }
}
