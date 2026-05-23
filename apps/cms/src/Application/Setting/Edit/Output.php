<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Edit;

use App\Cms\Application\Setting\Edit\Value\Settings;

final readonly class Output
{
    public function __construct(public Settings $settings)
    {
    }
}
