<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\Save;

use App\Cms\Domain\Repository\ITaxonomyRepository;
use App\Shared\Validation\FormValidator;
use App\Shared\Validation\ValidationInput;

final readonly class Interactor
{
    public function __construct(private ITaxonomyRepository $taxonomy)
    {
    }

    public function execute(Input $input): Output
    {
        $type = $this->stringValue(value: $input->data['type'] ?? 'category', default: 'category');
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

    private function stringValue(mixed $value, string $default = ''): string
    {
        return is_scalar($value) ? (string) $value : $default;
    }
}
