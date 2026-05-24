<?php

declare(strict_types=1);

namespace App\Cms\Application\Page\Edit;

use App\Cms\Application\Page\Edit\Value\Page;

final readonly class Output
{
    public function __construct(public ?Page $page)
    {
    }
}
