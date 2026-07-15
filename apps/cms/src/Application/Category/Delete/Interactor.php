<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Delete;

use App\Cms\Domain\Repository\ICategoryRepository;
use App\Cms\Domain\Value\CategoryId;

final readonly class Interactor
{
    public function __construct(private ICategoryRepository $categories)
    {
    }

    public function execute(Input $input): Output
    {
        $categoryId = new CategoryId(value: $input->id);
        $deleted = $this->categories->delete($categoryId);
        if (!$deleted) {
            return new Output(deleted: false, error: '対象が見つからないか、すでに削除されています。');
        }

        return new Output(deleted: true);
    }
}
