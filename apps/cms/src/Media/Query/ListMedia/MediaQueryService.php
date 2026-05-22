<?php

declare(strict_types=1);

namespace App\Cms\Media\Query\ListMedia;

interface MediaQueryService
{
    /** @return array<int, array<string, mixed>> */
    public function list(): array;
}
