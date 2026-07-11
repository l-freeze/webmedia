<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Update;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Category;
use App\Cms\Domain\Repository\ICategoryRepository;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(private ICategoryRepository $categories)
    {
    }

    public function execute(Input $input): Output
    {
        $validation = ValidationOutput::invalid(...array_values(array_filter([
            self::required(field: 'name', label: '名前', value: $input->name),
            self::required(field: 'slug', label: 'スラッグ', value: $input->slug),
        ])));
        $validation = SlugValidator::validate(slug: $input->slug, validation: $validation);

        if (!$validation->isValid()) {
            return new Output(updated: false, error: $validation->firstMessage());
        }

        $category = new Category(
            name: $input->name,
            slug: $input->slug,
            description: $input->description,
        );

        $updated = $this->categories->update(id: $input->id, category: $category);
        if (!$updated) {
            return new Output(updated: false, error: '対象が見つからないか、同じスラッグがすでに使われています。');
        }

        return new Output(updated: true);
    }

    private static function required(string $field, string $label, string $value): ?ValidationError
    {
        return $value === '' ? new ValidationError(field: $field, message: "{$label}を入力してください。") : null;
    }
}
