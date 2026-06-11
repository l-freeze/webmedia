<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Update;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Repository\ITagRepository;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationInput;

final readonly class Interactor
{
    public function __construct(private ITagRepository $tags)
    {
    }

    public function execute(Input $input): Output
    {
        $values = new ValidationInput(values: $input->data);
        $validation = (new FormValidator(input: $values))
            ->required(field: 'name', label: '名前')
            ->required(field: 'slug', label: 'スラッグ')
            ->output();
        $validation = SlugValidator::validate(input: $values, validation: $validation);

        if (!$validation->isValid()) {
            return new Output(updated: false, error: $validation->firstMessage());
        }

        if (!$this->tags->update(id: $input->id, data: $input->data)) {
            return new Output(updated: false, error: '対象が見つからないか、同じスラッグがすでに使われています。');
        }

        return new Output(updated: true);
    }
}
