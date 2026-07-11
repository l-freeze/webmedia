<?php

declare(strict_types=1);

namespace App\Cms\Application\Article\Restore;

use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;

final readonly class Input
{
    private function __construct(public int $articleId, public int $versionNo, public CurrentCmsUser $currentUser)
    {
    }

    public static function create(int $articleId, int $versionNo, CurrentCmsUser $currentUser): self
    {
        return new self(articleId: $articleId, versionNo: $versionNo, currentUser: $currentUser);
    }
}
