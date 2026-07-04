<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Restore;

use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

final readonly class Input
{
    public function __construct(public int $articleId, public int $versionNo, public CurrentCmsUser $currentUser)
    {
    }
}
