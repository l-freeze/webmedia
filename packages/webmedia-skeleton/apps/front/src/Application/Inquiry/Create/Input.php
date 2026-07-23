<?php

declare(strict_types=1);

namespace App\Front\Application\Inquiry\Create;

final readonly class Input
{
    private function __construct(
        public string $name,
        public string $email,
        public string $subject,
        public string $message,
    ) {
    }

    public static function create(string $name, string $email, string $subject, string $message): self
    {
        return new self(
            name: trim($name),
            email: trim($email),
            subject: trim($subject),
            message: $message,
        );
    }
}
