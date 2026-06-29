<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Audit;

final readonly class CmsAuditSummary
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $action,
        public string $resourceType,
        public ?string $resourceId,
        public array $metadata,
    ) {
    }
}
