<?php

declare(strict_types=1);

namespace App\Cms\Application\AdminUser\Update;

use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;

final readonly class Input
{
    /** @param array<string, mixed> $data */
    public function __construct(public int $id, public array $data, public CurrentCmsUser $currentUser)
    {
    }
}
