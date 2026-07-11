<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Save;

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
            return new Output(saved: false, error: $validation->firstMessage());
        }

        $slugExists = $this->categories->slugExists(slug: $input->slug, exceptId: null);
        if ($slugExists) {
            return new Output(saved: false, error: 'このスラッグは既に使われています。');
        }

        $category = new Category(
            name: $input->name,
            slug: $input->slug,
            description: $input->description,
        );
        $this->categories->create($category);

        return new Output(saved: true);
    }

    private static function required(string $field, string $label, string $value): ?ValidationError
    {
        return $value === '' ? new ValidationError(field: $field, message: "{$label}を入力してください。") : null;
    }
}
