<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Update;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Category;
use App\Cms\Domain\Repository\ICategoryRepository;
use App\Cms\Domain\Value\CategoryDescription;
use App\Cms\Domain\Value\CategoryId;
use App\Cms\Domain\Value\CategoryName;
use App\Cms\Domain\Value\CategorySlug;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(private ICategoryRepository $categories)
    {
    }

    public function execute(Input $input): Output
    {
        $candidates = [
            self::required(field: 'name', label: '名前', value: $input->name),
            self::required(field: 'slug', label: 'スラッグ', value: $input->slug),
        ];
        $errors = [];
        foreach ($candidates as $error) {
            if ($error !== null) {
                $errors[] = $error;
            }
        }
        $validation = ValidationOutput::invalid(...$errors);
        $validation = SlugValidator::validate(slug: $input->slug, validation: $validation);

        if (!$validation->isValid()) {
            return new Output(updated: false, error: $validation->firstMessage());
        }

        $categoryId = new CategoryId(value: $input->id);
        $slug = new CategorySlug(value: $input->slug);
        $slugExists = $this->categories->slugExists(slug: $slug, exceptId: $categoryId);
        if ($slugExists) {
            return new Output(updated: false, error: '対象が見つからないか、同じスラッグがすでに使われています。');
        }

        $category = new Category(
            id: $categoryId,
            name: new CategoryName(value: $input->name),
            slug: $slug,
            description: new CategoryDescription(value: $input->description),
        );

        $savedId = $this->categories->save($category);
        if ($savedId === null) {
            return new Output(updated: false, error: '対象が見つからないか、同じスラッグがすでに使われています。');
        }

        return new Output(updated: true);
    }

    private static function required(string $field, string $label, string $value): ?ValidationError
    {
        return $value === '' ? new ValidationError(field: $field, message: "{$label}を入力してください。") : null;
    }
}
