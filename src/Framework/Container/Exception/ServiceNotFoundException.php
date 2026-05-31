<?php

declare(strict_types=1);

namespace App\Framework\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class ServiceNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
