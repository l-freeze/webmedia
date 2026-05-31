<?php

declare(strict_types=1);

namespace App\Framework\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

final class ContainerBuildException extends RuntimeException implements ContainerExceptionInterface
{
}
