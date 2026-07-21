<?php

declare(strict_types=1);

namespace App\{{ namespace }}\Application\Contact\Submit;

use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationInput;

final readonly class Interactor
{
    public function execute(Input $input): Output
    {
        $validation = (new FormValidator(input: new ValidationInput(values: [
            'name' => $input->name,
            'email' => $input->email,
            'message' => $input->message,
        ])))
            ->required(field: 'name', label: '名前')
            ->required(field: 'email', label: 'メールアドレス')
            ->email(field: 'email', label: 'メールアドレス')
            ->required(field: 'message', label: '内容')
            ->output();

        if (!$validation->isValid()) {
            return Output::invalid(validation: $validation);
        }

        return Output::accepted();
    }
}
