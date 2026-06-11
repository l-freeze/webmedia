<?php

declare(strict_types=1);

namespace App\Cms\Application\Category\List;

use App\Cms\Application\Category\List\Query\ICategoryQuery;

final readonly class Interactor
{
    public function __construct(private ICategoryQuery $categories)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(categories: $this->categories->categories());
    }
}
