<?php

declare(strict_types=1);

namespace App\Cms\Application\Inquiry\Update;

use App\Shared\Validation\ValidationOutput;

final readonly class Output
{
    public function __construct(
        public bool $updated,
        public ValidationOutput $validation,
    ) {
    }

    public static function updated(): self
    {
        return new self(updated: true, validation: ValidationOutput::valid());
    }

    public static function invalid(ValidationOutput $validation): self
    {
        return new self(updated: false, validation: $validation);
    }
}
