<?php

declare(strict_types=1);

namespace App\Framework\Validation;

final class FormValidator
{
    /** @var list<ValidationError> */
    private array $errors = [];

    public function __construct(private readonly ValidationInput $input)
    {
    }

    public function required(string $field, string $label): self
    {
        if ($this->input->string(field: $field) === '') {
            $this->errors[] = new ValidationError(field: $field, message: "{$label}を入力してください。");
        }

        return $this;
    }

    public function email(string $field, string $label): self
    {
        $value = $this->input->string(field: $field);
        if ($value !== '' && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
            $this->errors[] = new ValidationError(field: $field, message: "{$label}を正しく入力してください。");
        }

        return $this;
    }

    public function output(): ValidationOutput
    {
        return new ValidationOutput(errors: $this->errors);
    }
}
