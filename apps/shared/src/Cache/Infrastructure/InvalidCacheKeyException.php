<?php

declare(strict_types=1);

namespace App\Shared\Cache\Infrastructure;

use InvalidArgumentException;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentException;

final class InvalidCacheKeyException extends InvalidArgumentException implements PsrInvalidArgumentException
{
}
