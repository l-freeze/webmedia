<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Page\Show;

use App\FrontBusiness\Application\Page\Show\Value\PageDetail;

final readonly class Output
{
    public function __construct(public ?PageDetail $page)
    {
    }
}
