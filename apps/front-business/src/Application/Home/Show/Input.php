<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Home\Show;

final readonly class Input
{
    public int $page;

    public function __construct(int $page = 1)
    {
        $this->page = max(1, $page);
    }
}
