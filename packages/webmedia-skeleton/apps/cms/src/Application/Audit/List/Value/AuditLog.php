<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List\Value;

final readonly class AuditLog
{
    public function __construct(
        public string $timestamp,
        public string $action,
        public string $resourceType,
        public string $resourceId,
        public string $result,
        public string $actorUserId,
        public string $detail,
    ) {
    }
}
