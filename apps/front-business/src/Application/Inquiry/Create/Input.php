<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Inquiry\Create;

final readonly class Input
{
    public function __construct(
        public string $name,
        public string $email,
        public string $subject,
        public string $message,
    ) {
    }
}
