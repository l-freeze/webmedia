<?php

declare(strict_types=1);

namespace App\Front\Application\Layout\Load\Query;

use App\Front\Application\Layout\Load\Value\SiteSettings;

interface ISiteSettingQuery
{
    public function current(): SiteSettings;
}
