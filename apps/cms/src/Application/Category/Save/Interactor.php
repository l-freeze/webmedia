<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Save;

use App\Cms\Domain\Repository\ICategoryRepository;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationInput;

final readonly class Interactor
{
    public function __construct(private ICategoryRepository $categories)
    {
    }

    public function execute(Input $input): Output
    {
        $values = new ValidationInput(values: $input->data);
        $validation = (new FormValidator(input: $values))
            ->required(field: 'name', label: '名前')
            ->required(field: 'slug', label: 'スラッグ')
            ->slug(field: 'slug', label: 'スラッグ')
            ->output();

        if (!$validation->isValid()) {
            return new Output(saved: false, error: $validation->firstMessage());
        }

        if ($this->categories->slugExists(slug: $values->string(field: 'slug'), exceptId: null)) {
            return new Output(saved: false, error: 'このスラッグは既に使われています。');
        }

        $this->categories->create(data: $input->data);

        return new Output(saved: true);
    }
}
