<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Security;

use App\Cms\Application\Auth\CurrentUser\Query\ICurrentCmsUserQuery;
use App\Cms\Application\Auth\CurrentUser\Value\CurrentCmsUser;
use App\Framework\Security\Session;

final class CmsGuard
{
    private bool $resolved = false;

    private ?CurrentCmsUser $currentUser = null;

    public function __construct(
        private readonly Session $session,
        private readonly ICurrentCmsUserQuery $users,
    ) {
    }

    public function currentUser(): ?CurrentCmsUser
    {
        if ($this->resolved) {
            return $this->currentUser;
        }

        $this->resolved = true;
        $userId = $this->userIdFromSession();
        if ($userId === null) {
            return null;
        }

        $this->currentUser = $this->users->findById(id: $userId);

        return $this->currentUser;
    }

    public function isAuthenticated(): bool
    {
        return $this->currentUser() !== null;
    }

    private function userIdFromSession(): ?int
    {
        $value = $this->session->get(key: 'cms.user_id');
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        return null;
    }
}
