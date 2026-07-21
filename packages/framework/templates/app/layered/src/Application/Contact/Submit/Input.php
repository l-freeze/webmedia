<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Application\Contact\Submit;

final readonly class Input
{
    public function __construct(
        public string $name,
        public string $email,
        public string $message,
    ) {
    }
}
