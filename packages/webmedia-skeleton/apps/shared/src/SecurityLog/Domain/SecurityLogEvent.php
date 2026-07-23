<?php

declare(strict_types=1);

namespace App\Shared\SecurityLog\Domain;

final readonly class SecurityLogEvent
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $eventType,
        public string $result,
        public ?string $actorUserId = null,
        public ?string $actorRole = null,
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
        public ?string $requestId = null,
        public array $metadata = [],
    ) {
    }
}
