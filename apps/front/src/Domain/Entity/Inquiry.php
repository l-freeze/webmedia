<?php

declare(strict_types=1);

namespace App\Front\Domain\Entity;

final readonly class Inquiry
{
    public function __construct(
        public string $name,
        public string $email,
        public string $subject,
        public string $message,
    ) {
    }
}
