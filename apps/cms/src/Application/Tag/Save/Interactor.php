<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Save;

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
        $values = new ValidationInput($input->data);
        $validation = (new FormValidator($values))
            ->required(field: 'name', label: '名前')
            ->required(field: 'slug', label: 'スラッグ')
            ->output();
        $validation = SlugValidator::validate(input: $values, validation: $validation);

        if (!$validation->isValid()) {
            return new Output(saved: false, error: $validation->firstMessage());
        }

        if ($this->tags->slugExists(slug: $values->string(field: 'slug'), exceptId: null)) {
            return new Output(saved: false, error: 'このスラッグは既に使われています。');
        }

        $this->tags->create([
            'name' => $values->string(field: 'name'),
            'slug' => $values->string(field: 'slug'),
        ]);

        return new Output(saved: true);
    }
}
