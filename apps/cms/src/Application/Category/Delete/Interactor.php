<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\Delete;

use App\Cms\Domain\Repository\ICategoryRepository;

final readonly class Interactor
{
    public function __construct(private ICategoryRepository $categories)
    {
    }

    public function execute(Input $input): Output
    {
        $deleted = $this->categories->delete($input->id);
        if (!$deleted) {
            return new Output(deleted: false, error: '対象が見つからないか、すでに削除されています。');
        }

        return new Output(deleted: true);
    }
}
