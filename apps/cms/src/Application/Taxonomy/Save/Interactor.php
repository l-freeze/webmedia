<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\Save;

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
        $type = (string) ($input->data['type'] ?? 'category');
        $validation = (new FormValidator(input: new ValidationInput(values: $input->data)))
            ->required(field: 'name', label: '名前')
            ->required(field: 'slug', label: 'スラッグ')
            ->output();

        if (!$validation->isValid()) {
            return new Output(saved: false, error: $validation->firstMessage());
        }

        if ($type === 'tag') {
            $this->taxonomy->createTag(data: $input->data);
            return new Output(saved: true);
        }

        $this->taxonomy->createCategory(data: $input->data);
        return new Output(saved: true);
    }
}
