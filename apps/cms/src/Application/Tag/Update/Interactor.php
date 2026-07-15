<?php

declare(strict_types=1);

namespace App\Cms\Application\Tag\Update;

use App\Cms\Application\Validation\SlugValidator;
use App\Cms\Domain\Entity\Tag;
use App\Cms\Domain\Repository\ITagRepository;
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

        $tag = new Tag(
            name: $input->name,
            slug: $input->slug,
        );

        $updated = $this->tags->update(id: $input->id, tag: $tag);
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
