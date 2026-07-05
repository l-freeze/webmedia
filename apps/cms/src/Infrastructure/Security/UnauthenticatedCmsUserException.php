<?php

declare(strict_types=1);

namespace App\Cms\Infrastructure\Security;

use RuntimeException;

final class UnauthenticatedCmsUserException extends RuntimeException
{
}
