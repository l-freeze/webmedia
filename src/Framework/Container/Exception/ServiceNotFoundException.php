<?php

declare(strict_types=1);

namespace Pera\Framework\Container\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

final class ServiceNotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
