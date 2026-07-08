<?php

declare(strict_types=1);

namespace App\FrontBusiness\Application\Category\List;

use App\FrontBusiness\Application\Category\List\Query\ICategoryQuery;

final readonly class Interactor
{
    public function __construct(private ICategoryQuery $categories)
    {
    }

    public function execute(Input $input): Output
    {
        return new Output(items: $this->categories->all());
    }
}
