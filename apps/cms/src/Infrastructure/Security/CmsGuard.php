<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Security;

use App\Cms\Application\Auth\GetCurrentUser\Input;
use App\Cms\Application\Auth\GetCurrentUser\Interactor;
use App\Cms\Application\Auth\GetCurrentUser\Value\CurrentCmsUser;
use App\Framework\Security\Session;

final class CmsGuard
{
    private bool $resolved = false;

    private ?CurrentCmsUser $currentUser = null;

    public function __construct(
        private readonly Session $session,
        private readonly Interactor $currentUsers,
    ) {
    }

    public function currentUser(): CurrentCmsUser
    {
        return $this->resolveCurrentUser()
            ?? throw new UnauthenticatedCmsUserException('Current CMS user is not authenticated.');
    }

    public function isAuthenticated(): bool
    {
        return $this->resolveCurrentUser() !== null;
    }

    private function resolveCurrentUser(): ?CurrentCmsUser
    {
        if ($this->resolved) {
            return $this->currentUser;
        }

        $this->resolved = true;
        $userId = $this->userIdFromSession();
        if ($userId === null) {
            return null;
        }

        $this->currentUser = $this->currentUsers->execute(Input::create(userId: $userId))->user;

        return $this->currentUser;
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
