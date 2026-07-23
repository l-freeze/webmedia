<?php

declare(strict_types=1);

namespace App\Front\Application\Page\Show;

use App\Front\Application\Page\Show\Value\PageDetail;

final readonly class Output
{
    public function __construct(public ?PageDetail $page)
    {
    }
}
