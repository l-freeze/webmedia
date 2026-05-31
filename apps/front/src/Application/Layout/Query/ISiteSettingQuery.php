<?php

declare(strict_types=1);

namespace App\Front\Application\Layout\Query;

use App\Front\Application\Layout\Value\SiteSettings;

interface ISiteSettingQuery
{
    public function current(): SiteSettings;
}
