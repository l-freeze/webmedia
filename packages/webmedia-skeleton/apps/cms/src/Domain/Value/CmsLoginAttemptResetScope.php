<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

enum CmsLoginAttemptResetScope: string
{
    case EmailIp = 'email_ip';
    case Ip = 'ip';
}
