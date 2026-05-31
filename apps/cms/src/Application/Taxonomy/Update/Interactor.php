<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\Update;

use App\Cms\Domain\Repository\ITaxonomyRepository;
use App\Framework\Validation\FormValidator;
use App\Framework\Validation\ValidationInput;

final readonly class Interactor
{
    public function __construct(private ITaxonomyRepository $taxonomy)
    {
    }

    public function execute(Input $input): Output
    {
        $validation = (new FormValidator(input: new ValidationInput(values: $input->data)))
            ->required(field: 'name', label: '名前')
            ->required(field: 'slug', label: 'スラッグ')
            ->output();

        if (!$validation->isValid()) {
            return new Output(updated: false, error: $validation->firstMessage());
        }

        $updated = match ($input->type) {
            'category' => $this->taxonomy->updateCategory(id: $input->id, data: $input->data),
            'tag' => $this->taxonomy->updateTag(id: $input->id, data: $input->data),
            default => false,
        };

        if (!$updated) {
            return new Output(updated: false, error: '対象が見つからないか、同じスラッグがすでに使われています。');
        }

        return new Output(updated: true);
    }
}
