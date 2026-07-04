<?php

declare(strict_types=1);

namespace App\Cms\Domain\Exception;

use RuntimeException;

final class ResourceOperationDeniedException extends RuntimeException
{
    public function __construct(
        public readonly int $actorId,
        public readonly string $operation,
    ) {
        parent::__construct("Resource operation denied: {$operation}");
    }
}
