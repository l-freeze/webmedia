<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Application\Contact\Submit;

use App\Shared\Validation\ValidationOutput;

final readonly class Output
{
    public function __construct(public bool $accepted, public ValidationOutput $validation)
    {
    }

    public static function accepted(): self
    {
        return new self(accepted: true, validation: ValidationOutput::valid());
    }

    public static function invalid(ValidationOutput $validation): self
    {
        return new self(accepted: false, validation: $validation);
    }

    /** @return array{accepted:bool,error:?string} */
    public function toArray(): array
    {
        return [
            'accepted' => $this->accepted,
            'error' => $this->validation->firstMessage(),
        ];
    }
}
