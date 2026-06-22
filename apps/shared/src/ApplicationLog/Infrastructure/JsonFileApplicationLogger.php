<?php

declare(strict_types=1);

namespace App\Shared\ApplicationLog\Infrastructure;

use App\Shared\ApplicationLog\Application\ApplicationLoggerInterface;
use App\Shared\Logging\Infrastructure\JsonFileLogger;

final class JsonFileApplicationLogger extends JsonFileLogger implements ApplicationLoggerInterface
{
}
