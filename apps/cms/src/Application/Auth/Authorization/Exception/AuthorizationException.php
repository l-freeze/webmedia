<?php

declare(strict_types=1);

namespace App\Cms\Application\Auth\Authorization\Exception;

use RuntimeException;

final class AuthorizationException extends RuntimeException
{
    public function __construct(
        public readonly int $userId,
        public readonly string $permission,
    ) {
        parent::__construct("CMS user {$userId} is not allowed to perform {$permission}.");
    }
}
