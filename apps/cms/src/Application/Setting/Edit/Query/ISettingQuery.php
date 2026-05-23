<?php

declare(strict_types=1);

namespace App\Cms\Application\Setting\Edit\Query;

use App\Cms\Application\Setting\Edit\Value\Settings;

interface ISettingQuery
{
    public function all(): Settings;
}
