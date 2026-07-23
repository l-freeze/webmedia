<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\List\Value;

final readonly class Inquiry
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $subject,
        public string $status,
        public string $statusLabel,
        public string $createdAt,
    ) {
    }

    /** @return array<int, string> */
    public function toArray(): array
    {
        return [(string) $this->id, $this->name, $this->email, $this->subject, $this->statusLabel, $this->createdAt];
    }
}
