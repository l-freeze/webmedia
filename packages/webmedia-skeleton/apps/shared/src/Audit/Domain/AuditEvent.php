<?php

declare(strict_types=1);

namespace App\Shared\Audit\Domain;

final readonly class AuditEvent
{
    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $action,
        public string $resourceType,
        public string $result,
        public ?string $actorUserId = null,
        public ?string $actorRole = null,
        public ?string $resourceId = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $requestId = null,
        public array $before = [],
        public array $after = [],
        public array $metadata = [],
    ) {
    }

    /** @param array<string, mixed> $metadata */
    public static function success(
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        ?string $actorUserId = null,
        array $metadata = [],
    ): self {
        return new self(
            action: $action,
            resourceType: $resourceType,
            result: 'success',
            actorUserId: $actorUserId,
            resourceId: $resourceId,
            metadata: $metadata,
        );
    }

    /** @param array<string, mixed> $metadata */
    public static function failure(
        string $action,
        string $resourceType,
        ?string $resourceId = null,
        ?string $actorUserId = null,
        array $metadata = [],
    ): self {
        return new self(
            action: $action,
            resourceType: $resourceType,
            result: 'failure',
            actorUserId: $actorUserId,
            resourceId: $resourceId,
            metadata: $metadata,
        );
    }
}
