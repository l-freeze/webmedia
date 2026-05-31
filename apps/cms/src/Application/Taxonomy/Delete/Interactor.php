<?php

declare(strict_types=1);

namespace App\Cms\Application\Taxonomy\Delete;

use App\Cms\Domain\Repository\ITaxonomyRepository;

final readonly class Interactor
{
    public function __construct(private ITaxonomyRepository $taxonomy)
    {
    }

    public function execute(Input $input): Output
    {
        $deleted = match ($input->type) {
            'category' => $this->taxonomy->deleteCategory(id: $input->id),
            'tag' => $this->taxonomy->deleteTag(id: $input->id),
            default => false,
        };

        if (!$deleted) {
            return new Output(deleted: false, error: '対象が見つからないか、すでに削除されています。');
        }

        return new Output(deleted: true);
    }
}
