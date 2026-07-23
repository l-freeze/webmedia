<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Update;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Tag;
use App\Cms\Domain\Repository\ITagRepository;
use App\Cms\Domain\Value\TagId;
use App\Cms\Domain\Value\TagName;
use App\Cms\Domain\Value\TagSlug;
use App\Shared\Validation\ValidationError;
use App\Shared\Validation\ValidationOutput;

final readonly class Interactor
{
    public function __construct(private ITagRepository $tags)
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

        $tagId = new TagId(value: $input->id);
        $slug = new TagSlug(value: $input->slug);
        $slugExists = $this->tags->slugExists(slug: $slug, exceptId: $tagId);
        if ($slugExists) {
            return new Output(updated: false, error: '対象が見つからないか、同じスラッグがすでに使われています。');
        }

        $tag = new Tag(
            id: $tagId,
            name: new TagName(value: $input->name),
            slug: $slug,
        );

        $savedId = $this->tags->save($tag);
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
