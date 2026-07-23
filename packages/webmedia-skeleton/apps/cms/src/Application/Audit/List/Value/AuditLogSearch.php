<?php

declare(strict_types=1);

namespace App\Cms\Application\Audit\List\Value;

final readonly class AuditLogSearch
{
    public function __construct(
        public string $timestampFrom = '',
        public string $timestampTo = '',
        public string $result = '',
        public string $action = '',
        public string $resourceType = '',
        public string $resourceId = '',
        public string $actorUserId = '',
    ) {
    }
}
